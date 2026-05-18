<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Get all students the teacher has assessed
$students_query = "SELECT DISTINCT s.id, s.name, s.grade 
                   FROM students s
                   JOIN assessments a ON a.student_id = s.id
                   WHERE a.teacher_id = $teacher_id
                   ORDER BY s.name";
$students = $conn->query($students_query);

// Get subjects the teacher teaches
$subjects_query = "SELECT DISTINCT subject FROM assessments WHERE teacher_id = $teacher_id ORDER BY subject";
$subjects = $conn->query($subjects_query);

// Get data for charts
$chart_data = [];
$performance_labels = ['EE', 'ME', 'AE', 'BE'];
$performance_colors = ['#00a651', '#2196F3', '#FF9800', '#f44336'];

// Overall performance distribution for this teacher
$overall_stats = $conn->query("SELECT performance_level, COUNT(*) as count 
                               FROM assessments 
                               WHERE teacher_id = $teacher_id 
                               GROUP BY performance_level");
$overall_counts = ['EE' => 0, 'ME' => 0, 'AE' => 0, 'BE' => 0];
while($row = $overall_stats->fetch_assoc()) {
    $overall_counts[$row['performance_level']] = $row['count'];
}

// Get data for selected student
$student_performance = [];
$subject_scores = [];
if ($selected_student_id > 0) {
    // Get performance by subject
    $subject_stats = $conn->query("SELECT subject, performance_level, COUNT(*) as count 
                                   FROM assessments 
                                   WHERE teacher_id = $teacher_id AND student_id = $selected_student_id 
                                   GROUP BY subject, performance_level");
    
    while($row = $subject_stats->fetch_assoc()) {
        if (!isset($subject_scores[$row['subject']])) {
            $subject_scores[$row['subject']] = ['EE' => 0, 'ME' => 0, 'AE' => 0, 'BE' => 0];
        }
        $subject_scores[$row['subject']][$row['performance_level']] = $row['count'];
    }
    
    // Get performance trend over time
    $trend_query = "SELECT DATE_FORMAT(assessment_date, '%Y-%m') as month, 
                           performance_level, COUNT(*) as count 
                    FROM assessments 
                    WHERE teacher_id = $teacher_id AND student_id = $selected_student_id 
                    GROUP BY month, performance_level 
                    ORDER BY month";
    $trend_result = $conn->query($trend_query);
    $trend_data = [];
    while($row = $trend_result->fetch_assoc()) {
        $trend_data[$row['month']][$row['performance_level']] = $row['count'];
    }
}

// Get top performing students
$top_students = $conn->query("SELECT s.name, COUNT(*) as total_ee 
                              FROM assessments a 
                              JOIN students s ON a.student_id = s.id 
                              WHERE a.teacher_id = $teacher_id AND a.performance_level = 'EE'
                              GROUP BY a.student_id 
                              ORDER BY total_ee DESC 
                              LIMIT 5");
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
    <h3 style="color: #00a651; margin-bottom: 20px;">📊 Assessment Progress Graphs</h3>
    
    <!-- Overall Stats -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h4 style="color: #00a651;">Overall Performance Distribution</h4>
            <canvas id="overallChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h4 style="color: #00a651;">Top Performing Students (EE Count)</h4>
            <canvas id="topStudentsChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
    
    <!-- Student Filter -->
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
        <h4 style="color: #00a651;">View Student Progress</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label>Select Student:</label>
                <select id="student_select" onchange="window.location.href='progress_graphs.php?student_id='+this.value+'&subject='+document.getElementById('subject_select').value" 
                        style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">-- Select Student --</option>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <option value="<?= $student['id'] ?>" <?= ($selected_student_id == $student['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($student['name']) ?> - <?= $student['grade'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Filter by Subject:</label>
                <select id="subject_select" onchange="window.location.href='progress_graphs.php?student_id=<?= $selected_student_id ?>&subject='+this.value" 
                        style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">-- All Subjects --</option>
                    <?php while($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?= $subject['subject'] ?>" <?= ($selected_subject == $subject['subject']) ? 'selected' : '' ?>>
                            <?= $subject['subject'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>
    
    <?php if ($selected_student_id > 0): ?>
        <!-- Student Performance -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="color: #00a651;">Student Performance by Subject</h4>
                <canvas id="studentSubjectChart" style="max-height: 300px;"></canvas>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="color: #00a651;">Performance Trend Over Time</h4>
                <canvas id="trendChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Overall Performance Chart
const overallCtx = document.getElementById('overallChart').getContext('2d');
new Chart(overallCtx, {
    type: 'doughnut',
    data: {
        labels: ['EE - Exceeding', 'ME - Meeting', 'AE - Approaching', 'BE - Below'],
        datasets: [{
            data: [<?= $overall_counts['EE'] ?>, <?= $overall_counts['ME'] ?>, <?= $overall_counts['AE'] ?>, <?= $overall_counts['BE'] ?>],
            backgroundColor: ['#00a651', '#2196F3', '#FF9800', '#f44336'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Top Students Chart
const topStudentsData = {
    labels: [<?php 
        $top_names = [];
        $top_counts = [];
        while($student = $top_students->fetch_assoc()) {
            $top_names[] = "'" . addslashes($student['name']) . "'";
            $top_counts[] = $student['total_ee'];
        }
        echo implode(',', $top_names);
    ?>],
    datasets: [{
        label: 'Number of EE Achievements',
        data: [<?= implode(',', $top_counts) ?>],
        backgroundColor: '#00a651',
        borderRadius: 5
    }]
};

const topCtx = document.getElementById('topStudentsChart').getContext('2d');
new Chart(topCtx, {
    type: 'bar',
    data: topStudentsData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

<?php if ($selected_student_id > 0 && !empty($subject_scores)): ?>
// Student Subject Performance
const subjectLabels = [<?php 
    $subj_names = [];
    $ee_data = [];
    $me_data = [];
    $ae_data = [];
    $be_data = [];
    foreach($subject_scores as $subject => $scores) {
        $subj_names[] = "'" . addslashes($subject) . "'";
        $ee_data[] = $scores['EE'];
        $me_data[] = $scores['ME'];
        $ae_data[] = $scores['AE'];
        $be_data[] = $scores['BE'];
    }
    echo implode(',', $subj_names);
?>];

const subjectCtx = document.getElementById('studentSubjectChart').getContext('2d');
new Chart(subjectCtx, {
    type: 'bar',
    data: {
        labels: subjectLabels,
        datasets: [
            { label: 'EE', data: [<?= implode(',', $ee_data) ?>], backgroundColor: '#00a651' },
            { label: 'ME', data: [<?= implode(',', $me_data) ?>], backgroundColor: '#2196F3' },
            { label: 'AE', data: [<?= implode(',', $ae_data) ?>], backgroundColor: '#FF9800' },
            { label: 'BE', data: [<?= implode(',', $be_data) ?>], backgroundColor: '#f44336' }
        ]
    },
    options: {
        responsive: true,
        scales: { x: { stacked: true }, y: { stacked: true } }
    }
});
<?php endif; ?>

<?php if ($selected_student_id > 0 && !empty($trend_data)): ?>
// Trend Chart
const trendLabels = [<?php 
    $months = [];
    foreach($trend_data as $month => $data) {
        $months[] = "'" . $month . "'";
    }
    echo implode(',', $months);
?>];

const eeTrend = [], meTrend = [], aeTrend = [], beTrend = [];
<?php foreach($trend_data as $month => $data): ?>
    eeTrend.push(<?= $data['EE'] ?? 0 ?>);
    meTrend.push(<?= $data['ME'] ?? 0 ?>);
    aeTrend.push(<?= $data['AE'] ?? 0 ?>);
    beTrend.push(<?= $data['BE'] ?? 0 ?>);
<?php endforeach; ?>

const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [
            { label: 'EE', data: eeTrend, borderColor: '#00a651', backgroundColor: 'rgba(0,166,81,0.1)', tension: 0.4, fill: true },
            { label: 'ME', data: meTrend, borderColor: '#2196F3', backgroundColor: 'rgba(33,150,243,0.1)', tension: 0.4, fill: true },
            { label: 'AE', data: aeTrend, borderColor: '#FF9800', backgroundColor: 'rgba(255,152,0,0.1)', tension: 0.4, fill: true },
            { label: 'BE', data: beTrend, borderColor: '#f44336', backgroundColor: 'rgba(244,67,54,0.1)', tension: 0.4, fill: true }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
<?php endif; ?>
</script>

</div>
</body>
</html>