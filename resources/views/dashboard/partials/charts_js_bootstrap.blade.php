<script>
  // Pass PHP -> JS data
  const ATTENDANCE = @json($charts['attendance']); // {labels:[], present:[], absent:[]}
  const ENROLMENT  = @json($charts['enrolment']);  // {labels:[], counts:[]}
  const FINANCE    = @json($charts['finance']);    // {labels:['Collected','Outstanding'], data:[..]}
  const EXAM       = @json($charts['exam']);       // {labels:[], avgs:[]}

  const fmt = (v) => new Intl.NumberFormat().format(v);

  // Attendance line
  if (document.getElementById('attendanceChart')) {
    new Chart(document.getElementById('attendanceChart'), {
      type: 'line',
      data: {
        labels: ATTENDANCE.labels,
        datasets: [
          { label: 'Present', data: ATTENDANCE.present, tension:.3, fill:false },
          { label: 'Absent', data: ATTENDANCE.absent, tension:.3, fill:false }
        ]
      },
      options: { plugins:{ legend:{ display:true } }, scales:{ y:{ beginAtZero:true } } }
    });
  }

  // Enrolment line
  if (document.getElementById('enrolmentChart')) {
    new Chart(document.getElementById('enrolmentChart'), {
      type: 'line',
      data: { labels: ENROLMENT.labels, datasets: [{ label:'Students', data: ENROLMENT.counts, tension:.3 }] },
      options: { scales:{ y:{ beginAtZero:true } } }
    });
  }

  // Finance donut
  if (document.getElementById('financeDonut')) {
    new Chart(document.getElementById('financeDonut'), {
      type: 'doughnut',
      data: { labels: FINANCE.labels, datasets: [{ data: FINANCE.data }] },
      options: { plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
    });
  }

  // Exam bar
  if (document.getElementById('examBar')) {
    new Chart(document.getElementById('examBar'), {
      type: 'bar',
      data: { labels: EXAM.labels, datasets: [{ label:'Average', data: EXAM.avgs }] },
      options: { scales:{ y:{ beginAtZero:true, max:100 } } }
    });
  }
</script>
