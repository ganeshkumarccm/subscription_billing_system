<!doctype html>
<html lang="en">
<body>
    <h1>Your subscription has been stopped</h1>
    <p>Hello {{ $subscription->customer->name }},</p>
    <p>Your subscription was stopped on {{ $subscription->updated_at?->utc()->format('Y-m-d H:i') }} UTC.</p>
    <p>No future subscription billing will be generated. Any invoices already issued remain available in your customer account.</p>
    <p>Thank you,<br>{{ config('app.name') }}</p>
</body>
</html>
