<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['study_plan'])) {
    echo "No study plan available. Please upload a file first.";
    exit;
}

$topics = $_SESSION['study_plan'];
$total_topics = count($topics);
$study_days = isset($_POST['study_days']) ? intval($_POST['study_days']) : 7;
$topics_per_day = ceil($total_topics / $study_days);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Plan</title>
    <link rel="stylesheet" href="style.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let currentPage = 1;
            const totalDays = <?php echo $study_days; ?>;
            
            function showPage(page) {
                document.querySelectorAll(".study-day").forEach((row, index) => {
                    row.style.display = (index + 1 === page) ? "table-row" : "none";
                });
                document.getElementById("page-number").innerText = `Day ${page} of ${totalDays}`;
                document.getElementById("prev-btn").disabled = (page === 1);
                document.getElementById("next-btn").disabled = (page === totalDays);
            }

            document.getElementById("prev-btn").addEventListener("click", function () {
                if (currentPage > 1) {
                    currentPage--;
                    showPage(currentPage);
                }
            });

            document.getElementById("next-btn").addEventListener("click", function () {
                if (currentPage < totalDays) {
                    currentPage++;
                    showPage(currentPage);
                }
            });

            showPage(currentPage);
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Your Study Plan</h2>
        <form method="POST" class="study-form">
            <label for="study_days">Enter study duration (days): </label>
            <input type="number" name="study_days" value="<?php echo $study_days; ?>" min="1" required>
            <button type="submit">Update Plan</button>
        </form>
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
            <p id="progress-text">0% Completed</p>
        </div>
        <div class="plan-overview">
            <h3>Plan Overview</h3>
            <div class="plan-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Topics</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                        for ($i = 0; $i < $study_days; $i++) {
                            echo "<tr class='study-day'><td>Day " . ($i + 1) . "</td><td><ul class='topic-list'>";
                            for ($j = $i * $topics_per_day; $j < min(($i + 1) * $topics_per_day, $total_topics); $j++) {
                                echo "<li>" . htmlspecialchars($topics[$j]) . "</li>";
                            }
                            echo "</ul></td><td><input type='checkbox' class='status-checkbox' onclick='updateProgress()'></td></tr>";
                        }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-controls">
                <button id="prev-btn">Previous</button>
                <span id="page-number">Day 1 of <?php echo $study_days; ?></span>
                <button id="next-btn">Next</button>
            </div>
        </div>
    <script>
        function updateProgress() {
            let checkboxes = document.querySelectorAll('.status-checkbox');
            let checkedCount = document.querySelectorAll('.status-checkbox:checked').length;
            let total = checkboxes.length;
            let progress = (checkedCount / total) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-text').innerText = Math.round(progress) + '% Completed';
        }
    </script>
        <a href="index.php" class="upload-link">Upload another file</a>
    </div>
</body>
</html>
