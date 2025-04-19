<h2>Welcome to Royal Kings School</h2>
<p>Hello {{ $user->name }},</p>

<p>Your account has been created. Here are your login credentials:</p>

<ul>
    <li><strong>Email:</strong> {{ $user->email }}</li>
    <li><strong>Password:</strong> {{ $password }}</li>
</ul>

<p>Please log in and change your password immediately.</p>

<p>Regards,<br>School Admin</p>
