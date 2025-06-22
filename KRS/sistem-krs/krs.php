<!DOCTYPE html>
<html>
<head>
    <title>KRS Page</title>
</head>
<body>

    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="krs.php">KRS</a></li>
            <li><a href="jadwal.php">Jadwal</a></li>
        </ul>
    </nav>

    <h1>KRS Page</h1>

    <form action="submit-krs.php" method="post">
        <label for="course1">Course 1:</label>
        <input type="text" id="course1" name="course1"><br><br>

        <label for="course2">Course 2:</label>
        <input type="text" id="course2" name="course2"><br><br>

        <input type="submit" value="Submit KRS">
    </form>

</body>
</html>
