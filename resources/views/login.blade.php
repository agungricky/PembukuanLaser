<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Member</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #ffffff, #eef2ff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        .login-box {
            background: #fff;
            padding: 40px 30px 30px;
            border-radius: 12px;
            width: 350px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            position: relative;
            animation: fadeIn 0.4s ease;
        }

        .avatar {
            width: 70px;
            height: 70px;
            background: #4f46e5;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 28px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        h2 {
            margin-top: 40px;
            /* lebih dekat ke avatar */
            margin-bottom: 18px;
            font-weight: 600;
            text-align: center;
            color: #333;
            font-size: 22px;
        }

        .form-group {
            position: relative;
            margin-bottom: 12px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background-color: #f3f6ff;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 5px rgba(79, 70, 229, 0.3);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }

        .toggle-password:hover {
            color: #4f46e5;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4f46e5;
            border: none;
            color: white;
            font-size: 15px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #4338ca;
        }

        a {
            display: block;
            margin-top: 14px;
            font-size: 13px;
            text-align: center;
            color: #4f46e5;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1d4ed8;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-box">
            <div class="avatar">
                <i class="fa fa-user"></i>
            </div>
            <h2>Halaman Login</h2>
            <form method="POST" action="{{ url('/login') }}">
                @csrf
                <div class="form-group">
                    <input type="text" name="name" placeholder="Username" value="{{ old('name') }}">
                    @error('name') <small style="color:red">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password">
                    <i class="fa fa-eye toggle-password" id="togglePassword"></i>
                    @error('password') <small style="color:red">{{ $message }}</small> @enderror
                </div>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        togglePassword.addEventListener('click', () => {
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>