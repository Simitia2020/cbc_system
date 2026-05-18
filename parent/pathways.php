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
    <h3 style="color: #00a651; margin-bottom: 20px;">🛤️ CBC Learning Pathways (Senior School)</h3>
    
    <p style="color: #666; margin-bottom: 20px;">After Grade 9, students choose from three career pathways:</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
            <h4 style="color: #2196F3;">🎓 Arts & Sports Science</h4>
            <p>For students talented in:</p>
            <ul style="margin-left: 20px;">
                <li>Performing Arts (Music, Drama, Dance)</li>
                <li>Visual Arts (Drawing, Painting, Sculpture)</li>
                <li>Sports (Athletics, Ball Games, Swimming)</li>
                <li>Languages and Literature</li>
            </ul>
        </div>
        
        <div style="background: #e8f5e9; padding: 20px; border-radius: 10px;">
            <h4 style="color: #00a651;">🔬 STEM</h4>
            <p>For students interested in:</p>
            <ul style="margin-left: 20px;">
                <li>Pure Sciences (Biology, Chemistry, Physics)</li>
                <li>Engineering and Technology</li>
                <li>Computer Science and ICT</li>
                <li>Mathematics and Statistics</li>
            </ul>
        </div>
        
        <div style="background: #fff3e0; padding: 20px; border-radius: 10px;">
            <h4 style="color: #FF9800;">💼 Social Sciences</h4>
            <p>For students passionate about:</p>
            <ul style="margin-left: 20px;">
                <li>Business Studies and Economics</li>
                <li>History and Government</li>
                <li>Geography and Environmental Studies</li>
                <li>Psychology and Sociology</li>
            </ul>
        </div>
    </div>
    
    <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; margin-top: 20px;">
        <h4 style="color: #9C27B0;">📌 Important Notes</h4>
        <ul style="margin-left: 20px;">
            <li>Pathway selection happens in Grade 9</li>
            <li>Career guidance and counseling provided</li>
            <li>Choice based on interests, strengths, and career aspirations</li>
            <li>Students can change pathways within first term of Senior School</li>
        </ul>
    </div>
</div>

</div>
</body>
</html>