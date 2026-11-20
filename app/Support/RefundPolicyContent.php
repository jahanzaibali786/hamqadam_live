<?php

namespace App\Support;

/**
 * Exact approved Refund, Cancellation & Exchange Policy supplied for
 * SafePay onboarding. Do not shorten or change this text in a deployment.
 * A published CMS revision may supersede it after administrator approval.
 */
final class RefundPolicyContent
{
    public static function defaults(): array
    {
        return [
            'title' => 'HAMQADAM – REFUND, CANCELLATION & EXCHANGE POLICY',
            'meta_title' => 'Refund, Cancellation & Exchange Policy | Hamqadam',
            'meta_description' => "Read Hamqadam's refund, cancellation and exchange policy, including subscription refund eligibility, payment issues and the 7-working-day refund processing timeline.",
            'sections' => [
                [
                    'heading' => 'Introduction',
                    'body' => '<p>Hamqadam App (Private) Limited is committed to providing reliable digital matrimonial services. This policy explains the conditions under which users may request refunds, cancellations, or resolution of payment-related issues when purchasing subscriptions or digital services through Hamqadam.</p>',
                ],
                [
                    'heading' => 'Subscription Payments',
                    'body' => '<p>Hamqadam offers digital subscription packages and premium features, including Gold, Platinum, VIP, and other paid services.</p><p>All subscription purchases are subject to the applicable package details, pricing, duration, and features displayed at the time of purchase.</p>',
                ],
                [
                    'heading' => 'Refund Eligibility',
                    'body' => '<p>Users may be eligible for a refund under the following circumstances:</p><ul><li><strong>Payment Deducted but Subscription Not Activated:</strong> If payment has been successfully deducted but the purchased subscription or package has not been activated due to a technical or system-related issue.</li><li><strong>Failed Transactions:</strong> If a transaction fails but the amount is deducted from the user&#039;s bank account, card, or digital wallet.</li><li><strong>Duplicate Payments:</strong> If a user is charged more than once for the same subscription or service.</li><li><strong>Technical Issues:</strong> If a verified technical issue prevents the user from accessing a purchased service or subscription.</li><li><strong>Incorrect Payment Processing:</strong> If an eligible payment-related error occurs during the purchase process.</li></ul><p>Each refund request will be reviewed and verified by the Hamqadam support team.</p>',
                ],
                [
                    'heading' => 'Non-Refundable Situations',
                    'body' => '<p>Refunds will generally not be provided in the following circumstances:</p><ul><li>The purchased subscription has been successfully activated and the user has accessed or utilized its benefits.</li><li>The user changes their mind after purchasing an activated subscription.</li><li>The subscription expires after its specified validity period.</li><li>The user is dissatisfied with personal matchmaking preferences or results.</li><li>The user&#039;s account is suspended or terminated due to violations of Hamqadam&#039;s Terms and Conditions.</li></ul><p>Exceptions may be considered where required by applicable law or where a verified service-related issue justifies a refund.</p>',
                ],
                [
                    'heading' => 'Cancellation Policy',
                    'body' => '<p>Users may contact Hamqadam support to request cancellation of a subscription or report a payment-related issue.</p><p>Cancellation of a subscription does not automatically qualify the user for a refund. Refund eligibility will be determined according to this policy.</p>',
                ],
                [
                    'heading' => 'Returns and Exchanges',
                    'body' => '<p>Since Hamqadam provides digital services and subscription-based features rather than physical products, physical returns and product exchanges are not applicable.</p><p>Where technically possible, Hamqadam may resolve eligible service issues by activating the purchased subscription, restoring access, or providing an appropriate service correction.</p>',
                ],
                [
                    'heading' => 'How to Request a Refund',
                    'body' => '<p>Users can submit a refund request through Hamqadam&#039;s official support channel or website contact facility.</p><p>To help process the request, users should provide:</p><ul><li>Registered Hamqadam account details.</li><li>Transaction ID or payment reference.</li><li>Purchased subscription/package details.</li><li>Payment date and amount.</li><li>A brief explanation of the issue.</li></ul><p>Hamqadam may request additional information to verify the transaction.</p>',
                ],
                [
                    'heading' => 'Refund Processing',
                    'body' => '<p>All eligible refund requests will be reviewed and processed in accordance with the refund turnaround time specified below.</p>',
                ],
                [
                    'heading' => 'Policy Updates',
                    'body' => '<p>Hamqadam reserves the right to update this policy when necessary. Any updates will be published on the official website.</p>',
                ],
            ],
            'turnaround_heading' => 'REFUND PROCESSING TIME: 7 WORKING DAYS',
            'turnaround_body' => '<p>Hamqadam is committed to resolving eligible payment-related issues promptly.</p><p>If a user&#039;s payment is successfully deducted but the purchased subscription or package is not activated due to a technical issue, failed transaction, duplicate payment, or other verified payment-related problem, the user may request a refund.</p><p>After verification and approval of an eligible refund request, Hamqadam will initiate the refund process within 7 working days.</p><p>The refund will be processed through the original payment method, wherever supported by the payment service provider.</p><p>Please note that the time required for the refunded amount to appear in the user&#039;s bank account, card, or digital wallet may vary depending on the payment service provider or financial institution.</p><p>For assistance, users can contact Hamqadam through its official support channels.</p>',
        ];
    }
}
