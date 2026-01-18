<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
</head>
<body>
    <h1>Payment Confirmation</h1>
    <p>Dear {{ $userName }},</p>
    <p>Thank you for your payment. Below are the details of your transaction:</p>

    <h2>Business Details</h2>
    <p><strong>Business Name:</strong> {{ $businessName }}</p>
    <p><strong>Service Plan:</strong> {{ $servicePlanName }}</p>
    <p><strong>Subscription Start Date:</strong> {{ $subscriptionStartDate }}</p>
    <p><strong>Subscription End Date:</strong> {{ $subscriptionEndDate }}</p>

    <h2>Payment Details</h2>
    <p><strong>Amount Paid:</strong> ${{ $amount }}</p>
    <p><strong>Transaction ID:</strong> {{ $transactionId }}</p>
    <p><strong>Payment Date:</strong> {{ $paymentDate }}</p>
    <p><strong>Payment Method:</strong> {{ $paymentMethod }}</p>

    <h2>Discount Details</h2>
    @if($discountCode)
        <p><strong>Discount Code:</strong> {{ $discountCode }}</p>
    @else
        <p><strong>Discount Code:</strong> Not Applied</p>
    @endif
    @if($discountAmount > 0)
        <p><strong>Discount Amount:</strong> ${{ $discountAmount }}</p>
    @else
        <p><strong>Discount Amount:</strong> $0</p>
    @endif

    <p>If you have any questions, feel free to contact us.</p>
    <p>Thank you!</p>
</body>
</html>
