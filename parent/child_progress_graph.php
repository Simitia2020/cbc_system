<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}

$parent_id = $_SESSION['user_id'];
$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get parent's children
$children = $conn->query("SELECT id, name, grade FROM students WHERE parent_id = $parent_id");

if ($children->num_rows == 0) {
    echo "<div style='background:white; padding:25px;'><p>No children linked. Please link your child first.</p></div>";
    exit();
}

// Get selected student data
$student_data = null;
$performance_data = ['EE' => 0, 'ME' => 0, 'AE' => 0, 'BE' => 0];
$subject_performance = [];

if ($selected_student_id > 0) {
    $student_query = $conn->prepare("SELECT name, grade FROM students WHERE id = ? AND parent_id = ?");
    $student_query->bind_param("ii", $selected_student_id, $parent_id);
    $student_query->execute();
    $student_data = $student_query->get_result()->fetch_assoc();
    
    if ($student_data) {
        // Get overall performance
        $stats = $conn->query("SELECT performance_level, COUNT(*) as count 
                               FROM assessments 
                               WHERE student_id = $selected_student_id 
                               GROUP BY performance_level");
        while($row = $stats->fetch_assoc()) {
            $performance_data[$row['performance_level']] = $row['count'];
        }
        
        // Get performance by subject
        $subjects = $conn->query("SELECT subject, performance_level, COUNT(*) as count 
                                  FROM assessments 
                                  WHERE student_id = $selected_student_id 
                                  GROUP BY subject, performance_level");
        while($row = $subjects->fetch_assoc()) {
            if (!isset($subject_performance[$row['subject']])) {
                $subject_performance[$row['subject']] = ['EE' => 0, 'ME' => 0, 'AE' => 0, 'BE' => 0];
            }
            $subject_performance[$row['subject']][$row['performance_level']] = $row['count'];
        }
    }
}
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651;">📊 Child Progress Graphs</h3>
    
    <!-- Child Selector -->
    <div style="background:#f8f9fa; padding:20px; border-radius:8px; margin:20px 0;">
        <select id="child_select" onchange="window.location.href='child_progress_graph.php?student_id='+this.value" 
                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            <option value="">-- Select Child --</option>
            <?php while($child = $children->fetch_assoc()): ?>
                <option value="<?= $child['id'] ?>" <?= ($selected_student_id == $child['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($child['name']) ?> - Grade <?= $child['grade'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    
    <?php if ($student_data && $selected_student_id > 0): ?>
        <h2 style="color:#333;"><?= htmlspecialchars($student_data['name']) ?></h2>
        <p style="color:#666;">Grade: <?= $student_data['grade'] ?></p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <div style="background:#f8f9fa; padding:20px; border-radius:10px;">
                <h4 style="color:#00a651;">Performance Distribution</h4>
                <canvas id="performanceChart" style="max-height: 300px;"></canvas>
            </div>
            <div style="background:#f8f9fa; padding:20px; border-radius:10px;">
                <h4 style="color:#00a651;">Performance by Subject</h4>
                <canvas id="subjectChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        
        <div style="background:#f8f9fa; padding:20px; border-radius:10px;">
            <h4 style="color:#00a651;">Performance Legend</h4>
            <div style="display:flex; gap:20px; justify-content:center;">
                <div><span style="background:#00a651; display:inline-block; width:20px; height:20px; border-radius:4px;"></span> EE - Exceeding</div>
                <div><span style="background:#2196F3; display:inline-block; width:20px; height:20px; border-radius:4px;"></span> ME - Meeting</div>
                <div><span style="background:#FF9800; display:inline-block; width:20px; height:20px; border-radius:4px;"></span> AE - Approaching</div>
                <div><span style="background:#f44336; display:inline-block; width:20px; height:20px; border-radius:4px;"></span> BE - Below</div>
            </div>
        </div>
    <?php elseif ($selected_student_id > 0): ?>
        <p>No assessment data available for this student.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($student_data && $selected_student_id > 0): ?>
// Performance Distribution Chart
const perfCtx = document.getElementById('performanceChart').getContext('2d');
new Chart(perfCtx, {
    type: 'doughnut',
    data: {
        labels: ['EE - Exceeding', 'ME - Meeting', 'AE - Approaching', 'BE - Below'],
        datasets: [{
            data: [<?= $performance_data['EE'] ?>, <?= $performance_data['ME'] ?>, <?= $performance_data['AE'] ?>, <?= $performance_data['BE'] ?>],
            backgroundColor: ['#00a651', '#2196F3', '#FF9800', '#f44336']
        }]
    }
});

// Subject Performance Chart
const subjectLabels = [<?php 
    $subjects_list = [];
    $ee_vals = []; $me_vals = []; $ae_vals = []; $be_vals = [];
    foreach($subject_performance as $subject => $scores) {
        $subjects_list[] = "'" . addslashes($subject) . "'";
        $ee_vals[] = $scores['EE'];
        $me_vals[] = $scores['ME'];
        $ae_vals[] = $scores['AE'];
        $be_vals[] = $scores['BE'];
    }
    echo implode(',', $subjects_list);
?>];

const subjectCtx = document.getElementById('subjectChart').getContext('2d');
new Chart(subjectCtx, {
    type: 'bar',
    data: {
        labels: subjectLabels,
        datasets: [
            { label: 'EE', data: [<?= implode(',', $ee_vals) ?>], backgroundColor: '#00a651' },
            { label: 'ME', data: [<?= implode(',', $me_vals) ?>], backgroundColor: '#2196F3' },
            { label: 'AE', data: [<?= implode(',', $ae_vals) ?>], backgroundColor: '#FF9800' },
            { label: 'BE', data: [<?= implode(',', $be_vals) ?>], backgroundColor: '#f44336' }
        ]
    },
    options: {
        responsive: true,
        scales: { x: { stacked: true }, y: { stacked: true } }
    }
});
<?php endif; ?>
</script>

</body>
</html>