<?php
require_once __DIR__ . '/config.php';

function create_payment_link($amount, $description, $returnUrl) {
  global $PAYMENTS_ENABLED, $MOLLIE_API_KEY, $STRIPE_SECRET_KEY;
  if (!$PAYMENTS_ENABLED) return null;

  // Placeholder: hier kun je Mollie of Stripe integreren.
  // Retourneer dan een URL waar de leerling kan betalen.
  return null;
}
?>
