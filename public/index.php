<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SendGrid\Mail\Mail;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$environment = $_ENV["ENVIRONMENT"] ?? "production";
if ($environment === "development") {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    $dotenv->required([
      'RECIPIENT_EMAIL',
      'SENDER_EMAIL'
    ]);
}

$app = AppFactory::create();

$app->get('/hello/{name}', function (
    Request $request,
    Response $response,
    array $args
) {
    $name = $args['name'];
    $response
        ->getBody()
        ->write(sprintf("Hello, %s.", ucfirst($name)));
    return $response;
});

$app->get('/send-email', function (
    Request $request,
    Response $response,
    array $args
) {
    $email = new Mail();

    if (
        ! array_key_exists('SENDER_EMAIL', $_ENV)
        || ! array_key_exists('RECIPIENT_EMAIL', $_ENV)
        || ! array_key_exists('SENDGRID_API_KEY', $_ENV)
    ) {
        $response->getBody()->write("An environment variable is missing or has not been set.");
        return $response->withStatus(400);
    }

    $email->setFrom($_ENV['SENDER_EMAIL']);
    $email->setSubject('Sending with Twilio SendGrid is Fun');
    // Replace the email address and name with your recipient
    $email->addTo($_ENV['RECIPIENT_EMAIL']);
    $email->addContent(
        'text/html',
        '<strong>and fast with the PHP SDK.</strong>'
    );
    $sendgrid = new \SendGrid($_ENV['SENDGRID_API_KEY']);
    try {
        $sendGridResponse = $sendgrid->send($email);
        $response
            ->getBody()
            ->write(sprintf("Response status: %d\n\n", $sendGridResponse->statusCode()));

        $headers = array_filter($sendGridResponse->headers());
        $response
            ->getBody()
            ->write(sprintf("Response Headers\n\n"));
        foreach ($headers as $header) {
            $response
                ->getBody()
                ->write('- ' . $header . "\n");
        }
    } catch (Exception $e) {
        $response
            ->getBody()
            ->write('Caught exception: ' . $e->getMessage() . "\n");
    }

    return $response;
});

$app->run();
