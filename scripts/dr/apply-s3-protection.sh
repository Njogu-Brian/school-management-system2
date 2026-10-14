#!/usr/bin/env bash
# Show, or apply, S3 Versioning and lifecycle rules for the disaster-recovery
# tier prefixes only. The existing hourly objects under private/backups/mysql/
# are not covered by these rules.
# Default is a dry run. Applying also requires CONFIRM_BUCKET_CHANGE=yes.
# This file is not applied by the repository tests.
# Object Lock is not enabled unless ENABLE_OBJECT_LOCK=1 is also set.
# Object Lock must be chosen deliberately: governance-locked versions are not
# removed by an ordinary delete, including when a lifecycle rule expires.

set -euo pipefail

APPLY="${APPLY:-0}"
CONFIRM_BUCKET_CHANGE="${CONFIRM_BUCKET_CHANGE:-no}"
ENABLE_OBJECT_LOCK="${ENABLE_OBJECT_LOCK:-0}"
BUCKET="${AWS_BACKUP_BUCKET:-${AWS_PRIVATE_BUCKET:-${AWS_BUCKET:-}}}"
REGION="${AWS_BACKUP_REGION:-${AWS_DEFAULT_REGION:-af-south-1}}"
ROOT_PREFIX="${AWS_BACKUP_PREFIX:-${AWS_PRIVATE_PREFIX:-private}}"
BASE_PREFIX="${BACKUP_S3_PREFIX:-backups/mysql}"
ROOT_PREFIX="${ROOT_PREFIX#/}"
ROOT_PREFIX="${ROOT_PREFIX%/}"
BASE_PREFIX="${BASE_PREFIX#/}"
BASE_PREFIX="${BASE_PREFIX%/}"
if [[ -n "$ROOT_PREFIX" ]]; then
  PREFIX="${ROOT_PREFIX}/${BASE_PREFIX}"
else
  PREFIX="$BASE_PREFIX"
fi

if [[ -z "$BUCKET" ]]; then
  echo "Set AWS_BACKUP_BUCKET or AWS_BUCKET to the backup bucket name." >&2
  exit 1
fi

if ! command -v aws >/dev/null 2>&1; then
  echo "The aws CLI is not installed. Versioning and lifecycle were not changed." >&2
  exit 1
fi

LIFECYCLE=$(mktemp)
trap 'rm -f "$LIFECYCLE"' EXIT

cat > "$LIFECYCLE" <<EOF
{
  "Rules": [
    {"ID": "expire-frequent-dumps", "Filter": {"Prefix": "${PREFIX}/frequent/"}, "Status": "Enabled", "Expiration": {"Days": 2}, "NoncurrentVersionExpiration": {"NoncurrentDays": 1}},
    {"ID": "expire-daily-dumps", "Filter": {"Prefix": "${PREFIX}/daily/"}, "Status": "Enabled", "Expiration": {"Days": 30}, "NoncurrentVersionExpiration": {"NoncurrentDays": 7}},
    {"ID": "expire-weekly-dumps", "Filter": {"Prefix": "${PREFIX}/weekly/"}, "Status": "Enabled", "Expiration": {"Days": 84}, "NoncurrentVersionExpiration": {"NoncurrentDays": 7}},
    {"ID": "expire-monthly-dumps", "Filter": {"Prefix": "${PREFIX}/monthly/"}, "Status": "Enabled", "Expiration": {"Days": 365}, "NoncurrentVersionExpiration": {"NoncurrentDays": 30}},
    {"ID": "expire-binlogs", "Filter": {"Prefix": "${PREFIX}/binlogs/"}, "Status": "Enabled", "Expiration": {"Days": 14}, "NoncurrentVersionExpiration": {"NoncurrentDays": 7}}
  ]
}
EOF

echo "Bucket: $BUCKET"
echo "Region: $REGION"
echo "Backup prefix: $PREFIX/"
echo "Versioning: Enabled"
echo "Lifecycle file:"
cat "$LIFECYCLE"
echo
echo "Binary logs expire after 14 days. Point-in-time recovery is therefore limited to 14 days."
echo "Older daily, weekly, and monthly dumps restore only to the moment that dump was taken."
echo "Uploader permissions are allow-only on private/backups/mysql/. See deploy/dr/backup-uploader-policy.json."
echo "Bucket administration is a separate policy: deploy/dr/backup-bucket-admin-policy.json."
echo "Do not attach either policy to the application user ERP-S3-Access."

if [[ "$PREFIX" != "private/backups/mysql" ]]; then
  echo "Refusing to continue. Lifecycle rules must stay under private/backups/mysql/." >&2
  exit 1
fi

if [[ "$APPLY" != "1" || "$CONFIRM_BUCKET_CHANGE" != "yes" ]]; then
  echo
  echo "Dry run only. Versioning and lifecycle were not changed."
  echo "To apply later, re-run with APPLY=1 and CONFIRM_BUCKET_CHANGE=yes."
  exit 0
fi

aws s3api put-bucket-versioning \
  --bucket "$BUCKET" \
  --region "$REGION" \
  --versioning-configuration Status=Enabled

aws s3api put-bucket-lifecycle-configuration \
  --bucket "$BUCKET" \
  --region "$REGION" \
  --lifecycle-configuration "file://${LIFECYCLE}"

echo "Versioning and lifecycle were applied to $BUCKET."

if [[ "$ENABLE_OBJECT_LOCK" == "1" ]]; then
  echo "Enabling Object Lock in GOVERNANCE mode with a 14-day default retention."
  echo "Locked versions cannot be removed by the backup user when a lifecycle rule expires."
  aws s3api put-object-lock-configuration \
    --bucket "$BUCKET" \
    --region "$REGION" \
    --object-lock-configuration '{"ObjectLockEnabled":"Enabled","Rule":{"DefaultRetention":{"Mode":"GOVERNANCE","Days":14}}}'
  echo "Object Lock governance retention was set to 14 days."
else
  echo "Object Lock was not changed. Set ENABLE_OBJECT_LOCK=1 only for a bucket prepared for it."
fi
