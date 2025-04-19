<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Royal Kings School Email</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color:rgb(191, 6, 215);
            color: #ffffff;
            padding: 25px;
            text-align: center;
        }
        .email-header img {
            height: 70px;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
            font-size: 16px;
            line-height: 1.7;
        }
        .email-body p {
            margin-bottom: 15px;
        }
        .email-footer {
            background-color: #f9f9f9;
            color: #555;
            text-align: center;
            font-size: 14px;
            padding: 25px;
        }
        .email-footer a {
            color: #002147;
            text-decoration: none;
        }
        .social-icons {
            margin-top: 12px;
        }
        .social-icons a {
            margin: 0 6px;
            display: inline-block;
        }
        .social-icons img {
            height: 22px;
            width: 22px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <img src="https://www.royalkingsschools.sc.ke/assets/images/logo.png" alt="Royal Kings School Logo">
            <h2>Royal Kings School</h2>
            <p>A sure Foundation Where Learning is Fun</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            {!! nl2br($content) !!}
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>Royal Kings School, Riverside - Wangige</p>
            <p>
                <a href="mailto:info@royalkingsschools.sc.ke">info@royalkingsschools.sc.ke</a> |
                <a href="tel:+254719396233">+254 719396233</a>
            </p>
            <p>
                <a href="https://www.royalkingsschools.sc.ke" target="_blank">
                    www.royalkingsschools.sc.ke
                </a>
            </p>
            <div class="social-icons">
                <a href="https://www.facebook.com/royalkingsschools" target="_blank">
                    <img src="https://www.royalkingsschools.sc.ke/assets/images/facebook.png" alt="Facebook">
                </a>
                <a href="https://www.instagram.com/royalkingsschools" target="_blank">
                    <img src="https://www.royalkingsschools.sc.ke/assets/images/instagram.png" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@royalkings_schools" target="_blank">
                    <img src="https://www.royalkingsschools.sc.ke/assets/images/tiktok.png" alt="TikTok">
                </a>
            </div>
            <p style="margin-top: 15px;">&copy; {{ date('Y') }} Royal Kings School. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
