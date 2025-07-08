<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - MPF</title>
    <style>
         body{
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #d1cecd;
            background-color: #180f27;
        }
        .main-div{
            width: 100vw;
            text-align: center;
            margin-top: 200px;
        }
        .main-div * {
            margin: auto;
        }
        .back-banner{
            transition: 3s ease-in-out;
            font-size: 20em;
            position: fixed;
            /* left: 50%; */
            right: 50%;
            transform: translate(50%);
            opacity: 8%;
            top: 0;
            z-index: -10;
            animation: fade 3s infinite;
        }
        @keyframes fade {
            0% {
                opacity: 8%;
            }
            50%{
                opacity: 16%;
            }
            100%{
                opacity: 8%;
            }
        }
    </style>
</head>
<body>
    <div class="main-div">
        <h1>MPF</h1>
        <h4 >MINIMALIST PHP FRAMEWORK</h4>
        <h6 style="border-bottom: 4px solid gray; padding: 20px 0 20px 0; width: 80%;">made by: adaomajor</h6>
        <h2>404</h2>
        <p class="back-banner">👽</p>
    </div>
</body>
</html>