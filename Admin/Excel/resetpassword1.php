<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome Icons  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"
        integrity="sha512-+4zCK9k+qNFUR5X+cKL9EIR+ZOhtIloNl9GIKS57V1MyNsYpYcUrUeQc9vNfzsWfV28IaLL3i96P9sdNyeRssA=="
        crossorigin="anonymous" />
    <link href="./assets/css/captcha.css" rel="stylesheet">
    

    <!-- Google Fonts  -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <style>
        /* body{
                width:100%;
                height: 100vh;
                background: linear-gradient(rgba(0,0,0,0.7),rgba(0,0,0,0.9)),
                url(./assets/images/bg2.jpg);
                background-position: center;
                background-size: cover;
                
            }
            div{
                color: #fff;
            } */
             body{
                background-color: white;
             }
             .box {
                width: 500px;
                height: 400px;
                background-color:rgb(80, 245, 20);
                color: white;
                display: flex;
                justify-content: center;
                align-items: center;
                border: 2px solid #333;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                border-radius: 20px;
            }
        </style>
    
</head>

<body>
<center>
<div class="box">
    
    <form action="requestreset.php" method="post">
        <div class="card">
        <h2>Reset Your Password</h2>
        <br>
        <p>You can reset your Password here</p>
        <p>Enter Your Email ID and a new Password will be emailed you.</p>
        <br>
        <p>Email ID:</p>
        <input type="email" name="email" class="passInput" placeholder="Email address">
        <br><br>
        <button type="submit" name="sendpassword" >Send My Password</button>
        </div>
    </form>
    </div></center>
</body>

</html>



