<?php
session_start();
include("../includes/sidebar.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../index.php");
    exit();
}
?>

<!-- Main Content Area -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="color: #00a651; margin-bottom: 20px;">📘 CBC Grading System</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #d4edda; padding: 20px; border-radius: 10px; text-align: center;">
            <h2 style="color: #00a651; font-size: 48px;">EE</h2>
            <p><strong>Exceeding Expectations</strong></p>
            <p>85% - 100%</p>
            <p style="font-size: 14px; color: #666;">Exceptional performance above grade level</p>
        </div>
        
        <div style="background: #cce5ff; padding: 20px; border-radius: 10px; text-align: center;">
            <h2 style="color: #2196F3; font-size: 48px;">ME</h2>
            <p><strong>Meeting Expectations</strong></p>
            <p>70% - 84%</p>
            <p style="font-size: 14px; color: #666;">Good performance at grade level</p>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 10px; text-align: center;">
            <h2 style="color: #FF9800; font-size: 48px;">AE</h2>
            <p><strong>Approaching Expectations</strong></p>
            <p>50% - 69%</p>
            <p style="font-size: 14px; color: #666;">Needs improvement to meet grade level</p>
        </div>
        
        <div style="background: #f8d7da; padding: 20px; border-radius: 10px; text-align: center;">
            <h2 style="color: #f44336; font-size: 48px;">BE</h2>
            <p><strong>Below Expectations</strong></p>
            <p>0% - 49%</p>
            <p style="font-size: 14px; color: #666;">Significant support required</p>
        </div>
    </div>
    
    <h4 style="color: #00a651; margin: 20px 0 10px;">Assessment Components</h4>
    <ul style="margin-left: 20px; color: #555; line-height: 1.8;">
        <li>📝 <strong>Term 1, 2, 3</strong> - Each term has Mid Term and End Term exams</li>
        <li>📚 <strong>Subjects vary by grade level</strong> - Lower primary (Grades 1-6) and Upper primary (Grades 7-9)</li>
        <li>🎯 <strong>Competency-based</strong> - Focus on skills and practical application</li>
        <li>📊 <strong>Continuous assessment</strong> - Multiple assessments throughout the term</li>
    </ul>
</div>

</div>
</body>
</html>