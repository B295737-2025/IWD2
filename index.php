<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protein Sequence Analysis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            line-height: 1.6;
            padding: 0 20px;
        }
        .box {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1f4e79;
        }
        label {
            display: inline-block;
            width: 160px;
            vertical-align: top;
            margin-top: 6px;
        }
        input[type="text"] {
            width: 320px;
            max-width: 100%;
            padding: 8px;
            margin-bottom: 12px;
        }
        input[type="submit"] {
            padding: 10px 16px;
            cursor: pointer;
        }
        .note {
            color: #555;
            font-size: 0.95em;
        }
        a {
            color: #1f4e79;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Protein Sequence Analysis Website</h1>

    <div class="box">
        <p>Enter a protein family and a taxonomic group to retrieve protein sequences.</p>

        <form action="fetch.php" method="post">
            <label for="user_name">User Name:</label>
            <input id="user_name" name="user_name" type="text" value="guest" maxlength="50" required><br>

            <label for="family">Protein family:</label>
            <input id="family" name="family" type="text" required><br>

            <label for="taxon">Taxonomic group:</label>
            <input id="taxon" name="taxon" type="text" required><br>

            <input type="submit" value="Fetch sequences">
        </form>

        <p class="note">
            The user name is only used as a history label to help filter previous queries.
            It is not a login or authentication system.
        </p>
    </div>
    <div class="box">
        <h2>Example dataset</h2>
        <p>
            Explore a pre-computed example dataset based on
            <strong>glucose-6-phosphatase proteins from Aves</strong>.
        </p>
        <p>
            <a href="example.php">View example dataset</a>
        </p>
    </div>
    
    <p><a href="history.php">View history</a></p>
    <p><a href="statement_of_credits.php">Statement of Credits</a></p>
</body>
</html>