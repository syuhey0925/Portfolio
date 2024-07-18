<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割表</title>
    <link rel="stylesheet" href="./css/popup.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php
    include "php/db_connect.php";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $date = $_POST['date'];
        $period = $_POST['period'];
        $class = $_POST['class'];
        $teacher_email = $_POST['teacher_email'];
        $subject = $_POST['subject'];
        $new_comment = $_POST['new_comment'];

        $updateSql = "UPDATE SCHEDULE SET comment = ? WHERE date = ? AND period = ? AND class = ? AND teacher_email = ? AND subject = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('ssssss', $new_comment, $date, $period, $class, $teacher_email, $subject);
        $stmt->execute();
        $stmt->close();
        echo '<script type="text/javascript">window.location.href = "";</script>';
        exit();
    }

    // ----- 要変更 ----------
    $userEmail = 'user1@example.com';   // ユーザー情報を取得
    $schedule = '1-1';  // 表示する時間割を指定(1-1, user1@example.com)
    // ----- 要変更 ここまで ----------

    $timezone = new DateTimeZone('Asia/Tokyo');
    $today = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime('now', $timezone);
    $startDate = (clone $today)->modify('Monday this week');
    $dates = [];
    $daysOfWeek = ['月', '火', '水', '木', '金', '土', '日'];
    $prevWeek = (clone $startDate)->modify('-1 week')->format('Y-m-d');
    $nextWeek = (clone $startDate)->modify('+1 week')->format('Y-m-d');

    // 前週ボタン
    echo "<a href='?date=$prevWeek' class='prev-week'><button class='prev-button'>前の週</button></a> &nbsp";


    for ($i = 0; $i < 7; $i++) {
        $dates[] = (clone $startDate)->modify("+$i day")->format('Y-m-d');
    }
    $stmt = $conn->prepare("SELECT is_class FROM SCHEDULE_LIST WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $schedule);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $isClass = $row['is_class'];
    $scheduleData = [];
    foreach ($dates as $date) {
        if ($isClass) {
            $stmt = $conn->prepare("SELECT * FROM SCHEDULE WHERE class = ? AND date = ? ORDER BY period");
        } else {
            $stmt = $conn->prepare("SELECT * FROM SCHEDULE WHERE teacher_email = ? AND date = ? ORDER BY period");
        }
        $stmt->bind_param("ss", $schedule, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $scheduleData[$date][$row['period']] = $row;
        }
    }

    echo "<table>";
    echo "<tr><th></th>";

    foreach ($dates as $index => $date) {
        echo "<th>" . date('m/d', strtotime($date)) . "（{$daysOfWeek[$index]}）</th>";
    }

    echo "</tr>";

    for ($period = 1; $period <= 6; $period++) {
        echo "<tr><td class='period'>{$period}時間目</td>";

        foreach ($dates as $date) {
            if (isset($scheduleData[$date][$period])) {
                $classInfo = $scheduleData[$date][$period];

                // 今何時間中何時間目かをsqlから取得して、それを変数に入れる
                // まずはその日の前日までの授業数を取得して、$past_totalに入れる
                $sql = "SELECT COUNT(*) AS past_total FROM SCHEDULE WHERE class = ? AND date <= ? - INTERVAL '1' DAY * 1 AND teacher_email = ? AND subject = ? GROUP BY class, teacher_email, subject;";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $schedule, $classInfo['date'], $classInfo['teacher_email'], $classInfo['subject']);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                // その日の前日までの授業がない場合は、$row['past_total']がNULLになるので、それを0にする
                if (!isset($row['past_total'])) {
                    $row['past_total'] = 0;
                } else {
                    $row['past_total'] = $row['past_total'];
                }
                $past_total = $row['past_total'];

                // 次に、その日の$periodで指定された時間までの授業数を取得して、$today_periodに入れる
                $sql = "SELECT COUNT(*) AS today_period FROM SCHEDULE WHERE class = ? AND date = ? AND teacher_email = ? AND subject = ? AND period <= ? GROUP BY class, teacher_email, subject;";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $schedule, $classInfo['date'], $classInfo['teacher_email'], $classInfo['subject'], $period);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $today_period = $row['today_period'];

                // 最後に今何時間中何時間目かを計算して、$now_totalに入れる
                $now_total = $past_total + $today_period;

                // テストまでその時間を含めてあと何時間あるかをsqlから取得して、それを変数に入れる
                // まずはその日の次の日からテストの時間までの授業数を取得して、$tomorrow_testに入れる
                $sql = "SELECT COUNT(*) - 1 AS tomorrow_exam FROM SCHEDULE ";
                $sql .= "WHERE class = ? AND teacher_email = ? AND subject = ? AND date >= ? + INTERVAL '1' DAY * 1 AND date <= ";
                $sql .= "(SELECT date FROM SCHEDULE WHERE class = ? AND teacher_email = ? AND subject = ? AND exam = '1');";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssss",
                    $schedule,
                    $classInfo['teacher_email'],
                    $classInfo['subject'],
                    $classInfo['date'],
                    $schedule,
                    $classInfo['teacher_email'],
                    $classInfo['subject']
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $tomorrow_exam = $row['tomorrow_exam'];

                // 次に、テストがない教科の場合に$tomorrow_examの値が-1であることを利用して、$to_examに'-'を入れておく
                if ($tomorrow_exam == -1) {
                    $to_exam = 'no_exam';
                } else {
                    // そうでない場合、その日の$periodで指定された時間からその日が終わるまでの授業数を取得して、$period_todayendに入れる
                    $sql = "SELECT COUNT(*) AS period_todayend FROM SCHEDULE WHERE class = ? AND date = ? AND teacher_email = ? AND subject = ? AND period >= ? GROUP BY class, teacher_email, subject;";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssss", $schedule, $classInfo['date'], $classInfo['teacher_email'], $classInfo['subject'], $period);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $period_todayend = $row['period_todayend'];

                    // 最後にテストまでその時間を含めてあと何時間あるかを計算して、$to_examに入れる
                    $to_exam = $tomorrow_exam + $period_todayend;
                }

                echo "<td data-user-email=\"$userEmail\" data-class-info=\"" . htmlspecialchars(json_encode($classInfo), ENT_QUOTES, 'UTF-8') . "\" data-now-total=\"$now_total\" data-to-exam=\"$to_exam\">";
                echo "<sb>{$classInfo['subject']}</sb> <br> <cl>$schedule <br> {$classInfo['teacher_name']}</cl>";

            } else {
                echo "<td></td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";

    // 次週ボタン
    echo "&nbsp <a href='?date=$nextWeek' class='next-week'><button class='next-button'>次の週</button></a>";
    
    $conn->close();
    ?>
    <script src="js/script.js" defer></script>
</body>

</html>