<?php
# vendor using composer
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_51IbNFFDfmUqTjyEZ3Epf1CA27pM8DOxpxKajGLA3EkzENJRXdvMHUKRxLKZqdQVFJ8GdDf9fbRCSS0nzjIihui0p00iS6YWC4B');

header('Content-Type: application/json');

# retrieve json from POST body
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

$intent = null;
try {
    if (isset($json_obj->payment_method_id)) {
        # Create the PaymentIntent
        $intent = \Stripe\PaymentIntent::create([
            'payment_method' => $json_obj->payment_method_id,
            'amount' => 1099,
            'currency' => 'usd',
            'confirmation_method' => 'manual',
            'confirm' => true,
        ]);
    }
    if (isset($json_obj->payment_intent_id)) {
        $intent = \Stripe\PaymentIntent::retrieve(
            $json_obj->payment_intent_id
        );
        $intent->confirm();
    }
    generateResponse($intent);
} catch (\Stripe\Exception\ApiErrorException $e) {
    # Display error on client
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}

function generateResponse($intent)
{
    # Note that if your API version is before 2019-02-11, 'requires_action'
    # appears as 'requires_source_action'.
    if ($intent->status == 'requires_action' &&
        $intent->next_action->type == 'use_stripe_sdk') {
        # Tell the client to handle the action
        echo json_encode([
            'requires_action' => true,
            'payment_intent_client_secret' => $intent->client_secret,
        ]);
    } else if ($intent->status == 'succeeded') {
        # The payment didn’t need any additional actions and completed!
        # Handle post-payment fulfillment
        echo json_encode([
            "success" => true,
        ]);
    } else {
        # Invalid status
        http_response_code(500);
        echo json_encode(['error' => 'Invalid PaymentIntent status']);
    }
}
