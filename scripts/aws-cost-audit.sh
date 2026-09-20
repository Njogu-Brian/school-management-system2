#!/bin/bash
# AWS Cost Audit — run this in AWS CloudShell (the terminal icon in the AWS console footer).
#
#   1. Open the AWS console, click the CloudShell icon (bottom-left).
#   2. Paste this whole file in, or upload it via Actions > Upload file, then:
#        bash aws-cost-audit.sh
#   3. Copy the entire output back.
#
# Read-only. Makes no changes to any resource.

set -uo pipefail

REGIONS="af-south-1 us-east-1"
THIS_MONTH_START=$(date -u +%Y-%m-01)
NEXT_MONTH_START=$(date -u -d "$(date -u +%Y-%m-01) +1 month" +%Y-%m-%d)
LAST_MONTH_START=$(date -u -d "$(date -u +%Y-%m-01) -1 month" +%Y-%m-%d)

hr() { printf '\n%s\n' "================================================================"; }
sec() { hr; echo "## $1"; hr; }

sec "1. Account identity"
aws sts get-caller-identity --output table

sec "2. Cost by SERVICE (last month + this month)"
aws ce get-cost-and-usage \
  --time-period Start="$LAST_MONTH_START",End="$NEXT_MONTH_START" \
  --granularity MONTHLY \
  --metrics UnblendedCost \
  --group-by Type=DIMENSION,Key=SERVICE \
  --query 'ResultsByTime[].{Month:TimePeriod.Start,Items:Groups[?Metrics.UnblendedCost.Amount!=`0`].{Service:Keys[0],Cost:Metrics.UnblendedCost.Amount}}' \
  --output json

sec "3. Cost by USAGE TYPE — this is the money question"
# Shows exactly which usage types cost what: BoxUsage (instance hours), CPUCredits,
# EBS:VolumeUsage, PublicIPv4, DataTransfer, S3 requests, etc.
aws ce get-cost-and-usage \
  --time-period Start="$LAST_MONTH_START",End="$NEXT_MONTH_START" \
  --granularity MONTHLY \
  --metrics UnblendedCost UsageQuantity \
  --group-by Type=DIMENSION,Key=USAGE_TYPE \
  --query 'ResultsByTime[].{Month:TimePeriod.Start,Items:Groups[?Metrics.UnblendedCost.Amount!=`0`].{UsageType:Keys[0],Cost:Metrics.UnblendedCost.Amount,Qty:Metrics.UsageQuantity.Amount}}' \
  --output json

sec "4. EC2 instances (all regions we care about)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws ec2 describe-instances --region "$R" \
    --query 'Reservations[].Instances[].{
        Id:InstanceId,
        Type:InstanceType,
        State:State.Name,
        Launched:LaunchTime,
        Arch:Architecture,
        AZ:Placement.AvailabilityZone,
        CreditMode:join(``,[`see section 5`]),
        PublicIp:PublicIpAddress,
        Name:Tags[?Key==`Name`].Value|[0]
      }' --output table 2>/dev/null || echo "(no access / no instances)"
done

sec "5. T-instance CPU credit mode (unlimited = you can be billed for surplus CPU)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  IDS=$(aws ec2 describe-instances --region "$R" \
        --filters "Name=instance-state-name,Values=running" \
        --query 'Reservations[].Instances[?starts_with(InstanceType,`t`)].InstanceId' \
        --output text 2>/dev/null)
  if [ -n "${IDS:-}" ]; then
    aws ec2 describe-instance-credit-specifications --region "$R" \
      --instance-ids $IDS --output table
  else
    echo "(no running t-family instances)"
  fi
done

sec "6. EBS volumes + snapshots (the 'EC2 - Other' line)"
for R in $REGIONS; do
  echo "--- region: $R volumes ---"
  aws ec2 describe-volumes --region "$R" \
    --query 'Volumes[].{Id:VolumeId,SizeGB:Size,Type:VolumeType,Iops:Iops,State:State,Attached:Attachments[0].InstanceId}' \
    --output table 2>/dev/null || echo "(none)"
  echo "--- region: $R snapshots owned by you ---"
  aws ec2 describe-snapshots --region "$R" --owner-ids self \
    --query 'Snapshots[].{Id:SnapshotId,SizeGB:VolumeSize,Started:StartTime,Desc:Description}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "7. Public IPv4 / Elastic IPs (the 'VPC' line — \$3.65/month each)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws ec2 describe-addresses --region "$R" \
    --query 'Addresses[].{Ip:PublicIp,AssociatedWith:InstanceId,Nic:NetworkInterfaceId,Idle:join(``,[`IDLE if AssociatedWith empty`])}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "8. NAT gateways + VPC endpoints (silent \$32/mo and \$7/mo killers)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws ec2 describe-nat-gateways --region "$R" \
    --query 'NatGateways[?State==`available`].{Id:NatGatewayId,Vpc:VpcId,State:State}' \
    --output table 2>/dev/null || echo "(no NAT gateways)"
  aws ec2 describe-vpc-endpoints --region "$R" \
    --query 'VpcEndpoints[].{Id:VpcEndpointId,Service:ServiceName,Type:VpcEndpointType}' \
    --output table 2>/dev/null || echo "(no VPC endpoints)"
done

sec "9. Load balancers (often forgotten, ~\$18/mo each)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws elbv2 describe-load-balancers --region "$R" \
    --query 'LoadBalancers[].{Name:LoadBalancerName,Type:Type,State:State.Code}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "10. RDS instances (should be NONE — MySQL is on the EC2 box)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws rds describe-db-instances --region "$R" \
    --query 'DBInstances[].{Id:DBInstanceIdentifier,Class:DBInstanceClass,Storage:AllocatedStorage,MultiAZ:MultiAZ}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "11. S3 buckets + sizes"
aws s3api list-buckets --query 'Buckets[].Name' --output text 2>/dev/null | tr '\t' '\n' | while read -r B; do
  [ -z "$B" ] && continue
  LOC=$(aws s3api get-bucket-location --bucket "$B" --query 'LocationConstraint' --output text 2>/dev/null)
  echo "--- bucket: $B (region: ${LOC:-us-east-1}) ---"
  aws s3 ls "s3://$B" --recursive --summarize 2>/dev/null | tail -3 || echo "(cannot list)"
  echo "lifecycle rules:"
  aws s3api get-bucket-lifecycle-configuration --bucket "$B" \
    --query 'Rules[].{Id:ID,Status:Status,Transitions:Transitions,Expiration:Expiration}' \
    --output json 2>/dev/null || echo "  (NONE — old objects never expire)"
done

sec "12. Secrets Manager (\$0.40/secret/month + API call charges)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws secretsmanager list-secrets --region "$R" \
    --query 'SecretList[].{Name:Name,LastAccessed:LastAccessedDate,Rotation:RotationEnabled}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "13. CloudWatch log groups WITHOUT retention (they grow forever)"
for R in $REGIONS; do
  echo "--- region: $R ---"
  aws logs describe-log-groups --region "$R" \
    --query 'logGroups[].{Name:logGroupName,RetentionDays:retentionInDays,StoredBytes:storedBytes}' \
    --output table 2>/dev/null || echo "(none)"
done

sec "14. Actual CPU utilisation of the EC2 box, last 14 days (daily avg + max)"
for R in $REGIONS; do
  IDS=$(aws ec2 describe-instances --region "$R" \
        --filters "Name=instance-state-name,Values=running" \
        --query 'Reservations[].Instances[].InstanceId' --output text 2>/dev/null)
  for I in ${IDS:-}; do
    echo "--- $R / $I : CPUUtilization ---"
    aws cloudwatch get-metric-statistics --region "$R" \
      --namespace AWS/EC2 --metric-name CPUUtilization \
      --dimensions Name=InstanceId,Value="$I" \
      --start-time "$(date -u -d '14 days ago' +%Y-%m-%dT%H:%M:%SZ)" \
      --end-time "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
      --period 86400 --statistics Average Maximum \
      --query 'sort_by(Datapoints,&Timestamp)[].{Day:Timestamp,AvgPct:Average,MaxPct:Maximum}' \
      --output table
    echo "--- $R / $I : CPUCreditBalance + CPUSurplusCreditsCharged (THIS IS THE BILLED ONE) ---"
    for M in CPUCreditBalance CPUSurplusCreditBalance CPUSurplusCreditsCharged; do
      echo "  metric: $M"
      aws cloudwatch get-metric-statistics --region "$R" \
        --namespace AWS/EC2 --metric-name "$M" \
        --dimensions Name=InstanceId,Value="$I" \
        --start-time "$(date -u -d '14 days ago' +%Y-%m-%dT%H:%M:%SZ)" \
        --end-time "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
        --period 86400 --statistics Average Maximum Sum \
        --query 'sort_by(Datapoints,&Timestamp)[].{Day:Timestamp,Avg:Average,Max:Maximum,Sum:Sum}' \
        --output table 2>/dev/null || echo "    (no data)"
    done
    echo "--- $R / $I : NetworkOut (data transfer driver) ---"
    aws cloudwatch get-metric-statistics --region "$R" \
      --namespace AWS/EC2 --metric-name NetworkOut \
      --dimensions Name=InstanceId,Value="$I" \
      --start-time "$(date -u -d '14 days ago' +%Y-%m-%dT%H:%M:%SZ)" \
      --end-time "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
      --period 86400 --statistics Sum \
      --query 'sort_by(Datapoints,&Timestamp)[].{Day:Timestamp,BytesOut:Sum}' \
      --output table
  done
done

sec "15. Existing Savings Plans / Reserved Instances (probably none)"
aws savingsplans describe-savings-plans --output table 2>/dev/null || echo "(none / no access)"
for R in $REGIONS; do
  aws ec2 describe-reserved-instances --region "$R" \
    --query 'ReservedInstances[?State==`active`].{Type:InstanceType,Count:InstanceCount,End:End}' \
    --output table 2>/dev/null || echo "(none in $R)"
done

sec "16. AWS's own right-sizing + Savings Plan recommendations"
aws ce get-savings-plans-purchase-recommendation \
  --savings-plans-type COMPUTE_SP --term-in-years ONE_YEAR \
  --payment-option NO_UPFRONT --lookback-period-in-days SIXTY_DAYS \
  --query 'SavingsPlansPurchaseRecommendation.{
      HourlyCommitment:SavingsPlansPurchaseRecommendationSummary.HourlyCommitmentToPurchase,
      EstMonthlySaving:SavingsPlansPurchaseRecommendationSummary.EstimatedMonthlySavingsAmount,
      EstSavingsPct:SavingsPlansPurchaseRecommendationSummary.EstimatedSavingsPercentage}' \
  --output table 2>/dev/null || echo "(not enough history / no access)"

hr
echo "DONE. Copy everything above."
hr
