<?php

return [
    'privacy' => [
        'title' => 'Privacy Policy',
        'effective_date' => 'Effective date: 4 July 2026',
        'lead' => 'E-Tali-Khata (&ldquo;we&rdquo;, &ldquo;us&rdquo;, the &ldquo;App&rdquo;) is a digital ledger (khata) app that helps small businesses record customers, suppliers, transactions, bills and cashbook entries, and optionally back that data up to the user&rsquo;s own Google Drive. This policy explains what information we collect, how we use it, and the choices you have &mdash; including exactly how we handle Google account and Google Drive data.',
        'sections' => [
            [
                'heading' => '1. Information we collect',
                'body' => '<ul>
                    <li><strong>Account information</strong> &mdash; your name, email address, phone number (optional), and a password (for email sign-up). Passwords are stored hashed.</li>
                    <li><strong>Google account information</strong> &mdash; if you choose &ldquo;Continue with Google&rdquo;, we receive your name, email address, and profile picture from Google to create and identify your account.</li>
                    <li><strong>Your ledger data</strong> &mdash; the businesses, customers, suppliers, transactions, bills/vouchers and cashbook entries that you enter into the App.</li>
                    <li><strong>Google Drive authorization</strong> &mdash; if you connect Google Drive, we securely store an authorization token so we can create and upload your backup files on your behalf (see Section 3).</li>
                    <li><strong>Basic technical data</strong> &mdash; information needed to operate the service reliably and securely.</li>
                </ul>',
            ],
            [
                'heading' => '2. How we use your information',
                'body' => '<ul>
                    <li>To create and secure your account and authenticate you.</li>
                    <li>To provide the ledger features you use and store your business records.</li>
                    <li>To create and upload backups of your data to your Google Drive when you request a backup or set an automatic schedule.</li>
                    <li>To manage your subscription/package and communicate service-related messages (e.g. email verification).</li>
                </ul>',
            ],
            [
                'heading' => '3. Google account &amp; Google Drive data',
                'body' => '<div class="legal-callout">
                    <p><strong>Google Sign-In.</strong> We use your Google email and profile only to create and log you into your E-Tali-Khata account.</p>
                    <p><strong>Google Drive (<code>drive.file</code> scope).</strong> The App requests the narrowest Drive permission possible. With this scope we can <strong>only create and access the backup files the App itself creates</strong> inside a dedicated &ldquo;Tali Khata Backups&rdquo; folder in <em>your</em> Google Drive. We <strong>cannot see, read, or access any of your other Google Drive files</strong>. Your backups stay in your own Drive and remain under your control.</p>
                </div>
                <p>To run backups you schedule (including when the App is closed), we store your Google authorization token <strong>encrypted</strong> on our server. You can disconnect Google Drive at any time from the App&rsquo;s Backup screen, which stops all further access.</p>',
            ],
            [
                'heading' => '4. Google API Services &mdash; Limited Use disclosure',
                'body' => '<p>E-Tali-Khata&rsquo;s use and transfer of information received from Google APIs will adhere to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Google API Services User Data Policy</a>, including the <strong>Limited Use</strong> requirements. Specifically, we do not use Google user data for advertising, we do not sell it, and we do not use it for any purpose other than providing and improving the backup and account features you request.</p>',
            ],
            [
                'heading' => '5. How we store and protect your data',
                'body' => '<p>Your data is stored on our secured backend server and transmitted over encrypted HTTPS connections. Google Drive authorization tokens are encrypted at rest. Backup files are placed in your own Google Drive; we do not retain copies of your Drive backup files on our servers.</p>',
            ],
            [
                'heading' => '6. Sharing and disclosure',
                'body' => '<p>We do <strong>not</strong> sell your personal information or your ledger data. We do not share it with third parties for their own marketing. We only disclose information where required by law, or to trusted service providers who help us operate the App under confidentiality obligations.</p>',
            ],
            [
                'heading' => '7. Data retention and your choices',
                'body' => '<ul>
                    <li><strong>Disconnect Drive</strong> &mdash; remove our access to your Google Drive at any time from the App&rsquo;s Backup screen.</li>
                    <li><strong>Delete your data / account</strong> &mdash; contact us at the email below to request deletion of your account and associated data.</li>
                    <li>You may also revoke the App&rsquo;s access from your Google Account at <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">myaccount.google.com/permissions</a>.</li>
                </ul>',
            ],
            [
                'heading' => '8. Children&rsquo;s privacy',
                'body' => '<p>The App is intended for business users and is not directed to children under 13. We do not knowingly collect data from children.</p>',
            ],
            [
                'heading' => '9. Changes to this policy',
                'body' => '<p>We may update this policy from time to time. Material changes will be reflected by updating the effective date above.</p>',
            ],
        ],
        'contact_heading' => '10. Contact us',
        'contact_intro' => 'Questions or data requests:',
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'effective_date' => 'Effective date: 4 July 2026',
        'lead' => 'These Terms govern your use of E-Tali-Khata (the &ldquo;App&rdquo;), a digital ledger app for small businesses. By creating an account or using the App, you agree to these Terms. If you do not agree, please do not use the App.',
        'sections' => [
            [
                'heading' => '1. The service',
                'body' => '<p>E-Tali-Khata lets you record businesses, customers, suppliers, transactions, bills and cashbook entries, and optionally back up your data to your own Google Drive. Features may change or improve over time.</p>',
            ],
            [
                'heading' => '2. Eligibility and accounts',
                'body' => '<ul>
                    <li>You must be able to form a binding contract and use the App for a lawful business purpose.</li>
                    <li>You are responsible for keeping your login credentials secure and for all activity under your account.</li>
                    <li>Email accounts must verify their email address before use; Google accounts are verified through Google.</li>
                </ul>',
            ],
            [
                'heading' => '3. Subscriptions, free trial and payments',
                'body' => '<ul>
                    <li>Access is provided through packages. New verified users receive a <strong>Free Trial</strong> automatically.</li>
                    <li>When a subscription expires, access to your business data may be locked until it is renewed. Your data is retained and becomes accessible again upon renewal.</li>
                    <li>Package details and validity are managed by the administrator. Any fees, where applicable, are communicated separately.</li>
                </ul>',
            ],
            [
                'heading' => '4. Your content and backups',
                'body' => '<ul>
                    <li><strong>You own your data.</strong> The business records you enter remain yours.</li>
                    <li>Backups are created in <em>your</em> Google Drive using the minimal <code>drive.file</code> permission, and remain under your control. You are responsible for maintaining access to your own Google account.</li>
                    <li>We are not responsible for the availability, limits, or actions of Google Drive or your Google account.</li>
                </ul>',
            ],
            [
                'heading' => '5. Acceptable use',
                'body' => '<p>You agree not to misuse the App, attempt to disrupt or reverse-engineer it, access other users&rsquo; data, or use it for any unlawful, fraudulent, or harmful purpose.</p>',
            ],
            [
                'heading' => '6. Google services',
                'body' => '<p>Google Sign-In and Google Drive backup are provided in accordance with our <a href=":privacy_url">Privacy Policy</a> and the Google API Services User Data Policy, including its Limited Use requirements. You can disconnect Google Drive at any time.</p>',
            ],
            [
                'heading' => '7. Disclaimers',
                'body' => '<p>The App is provided &ldquo;as is&rdquo; and &ldquo;as available&rdquo;, without warranties of any kind. While we work to keep your data safe, we do not guarantee uninterrupted or error-free operation. Please keep your own backups where important.</p>',
            ],
            [
                'heading' => '8. Limitation of liability',
                'body' => '<p>To the maximum extent permitted by law, E-Tali-Khata and its operators are not liable for any indirect, incidental, or consequential damages, or for any loss of data arising from your use of the App or from third-party services such as Google Drive.</p>',
            ],
            [
                'heading' => '9. Termination',
                'body' => '<p>You may stop using the App at any time. We may suspend or terminate access for violations of these Terms. You may request deletion of your account and data by contacting us.</p>',
            ],
            [
                'heading' => '10. Governing law',
                'body' => '<p>These Terms are governed by the laws of the People&rsquo;s Republic of Bangladesh, without regard to conflict-of-law principles.</p>',
            ],
            [
                'heading' => '11. Changes to these Terms',
                'body' => '<p>We may update these Terms from time to time. Continued use of the App after changes means you accept the updated Terms.</p>',
            ],
        ],
        'contact_heading' => '12. Contact',
        'contact_intro' => 'Questions about these Terms:',
    ],
];
