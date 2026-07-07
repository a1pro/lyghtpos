<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            background: #22c55e;
            align-items: center;
            padding: 20px 40px;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .login-btn {
            padding: 10px 22px;
            border-radius: 6px;
            background: #ffffff;
            color: #022c22;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #16a34a;
            color: #ffffff;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            line-height: 1.7;
        }


        /* Main Content */
        .content {
            padding: 40px;
            overflow-wrap: break-word;
        }

        .content h1 {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .content p {
            font-size: 18px;
            max-width: 600px;
            opacity: 0.85;
            line-height: 1.6;
        }

        .badge {
            margin-top: 30px;
            padding: 10px 20px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 14px;
            opacity: 0.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content h1 {
                font-size: 36px;
            }

            .header {
                padding: 15px 20px;
            }
        }

    </style>

</head>
<body class="coming-soon">

    <!-- Header -->
    <div class="header">
        <div class="logo">Lyght POS</div>

        @auth
        @php
        $user = Auth::user();
        @endphp

        @if($user->role_id == 1)
        <a href="{{ url('/admin') }}" class="login-btn">Admin</a>
        @elseif($user->role_id == 2)
        <a href="{{ route('merchant.dashboard') }}" class="login-btn">Merchant Dashboard</a>
        @endif

        <!-- Optional: Add logout button -->
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>

        @else
        <a href="{{ url('login') }}" class="login-btn">Login</a>
        @endauth
    </div>


    <!-- Main Content -->
    <div class="content">
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">PRIVACY POLICY FOR LYGHT POX<br /><strong>Effective Date: July 6, 2026</strong><br /><strong>Last Updated: July 6, 2026</strong></pre>
        <h2>&nbsp;</h2>
        <h2>1. INTRODUCTION</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Welcome to LYGHT POX.<br />LYGHT POX is a community-centered point-of-exchange application operated through UNYTI, LLC as part of the broader Communyti ecosystem.<br />LYGHT POX is designed to help participating individuals, merchants, businesses, nonprofit organizations, sponsors, program administrators, and other approved participants access and use eligible vouchers, tokens, rewards, pledged value, benefits, and other approved exchange options.<br />This Privacy Policy explains how LYGHT POX, UNYTI, LLC, and authorized service providers supporting the LYGHT POX platform may collect, use, process, store, protect, and disclose information when you:<br />&bull; download or use the LYGHT POX mobile application;<br />&bull; create or maintain an account;<br />&bull; participate in an approved network, program, campaign, or community initiative;<br />&bull; receive, present, transfer, redeem, or apply eligible value;<br />&bull; conduct activity with a participating merchant or organization;<br />&bull; communicate with customer support;<br />&bull; interact with connected services or features made available through LYGHT POX.<br />In this Privacy Policy, the terms &ldquo;LYGHT POX,&rdquo; &ldquo;we,&rdquo; &ldquo;us,&rdquo; and &ldquo;our&rdquo; refer to the LYGHT POX platform and its authorized operators. The terms &ldquo;user,&rdquo; &ldquo;you,&rdquo; and &ldquo;your&rdquo; refer to individuals or authorized representatives who access or use the application.<br />By using LYGHT POX, you acknowledge the data practices described in this Privacy Policy.</pre>
        <h2>&nbsp;</h2>
        <h2>2. THE ROLE OF LYGHT POX</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />LYGHT POX functions as a point-of-exchange interface within a broader community ecosystem.<br />Depending upon the participating program, organization, merchant, or network, the application may allow users to:<br />&bull; create and maintain a user account;<br />&bull; access an available balance;<br />&bull; access eligible vouchers, rewards, tokens, credits, or pledged value;<br />&bull; present eligible value to a participating merchant or organization;<br />&bull; verify eligibility or authorization;<br />&bull; redeem or apply approved value;<br />&bull; view transaction and account activity;<br />&bull; participate in approved community programs or campaigns;<br />&bull; interact with participating businesses and organizations;<br />&bull; receive service messages and important account notifications.<br />The exact features available to a user may differ according to the user's role, participating network, program, merchant relationship, organization, geographic availability, and account permissions.</pre>
        <h2>&nbsp;</h2>
        <h2>3. INFORMATION WE MAY COLLECT</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />We collect information reasonably necessary to provide, administer, secure, and improve LYGHT POX.<br />The information collected depends upon how you use the application and which features are enabled for your account or participating network.</pre>
        <h3>&nbsp;</h3>
        <h3>A. Account and Registration Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">When an account is created or administered, we may collect information such as:<br />&bull; first and last name;<br />&bull; display name;<br />&bull; email address;<br />&bull; telephone number;<br />&bull; username or account identifier;<br />&bull; password or authentication credentials in secured or encrypted form;<br />&bull; user role or account type;<br />&bull; participating organization, merchant, or network affiliation;<br />&bull; account status;<br />&bull; communication preferences.<br />We use this information to establish and maintain accounts, authenticate users, provide appropriate access, communicate with users, and protect the platform from unauthorized use.</pre>
        <h3>&nbsp;</h3>
        <h3>B. Business and Organization Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">For merchants, businesses, nonprofit organizations, sponsors, and other participating entities, we may collect:<br />&bull; business or organization name;<br />&bull; representative or contact name;<br />&bull; business address;<br />&bull; telephone number;<br />&bull; email address;<br />&bull; website information;<br />&bull; business category;<br />&bull; program or network affiliation;<br />&bull; merchant or organization identification information;<br />&bull; participation status;<br />&bull; authorized user information;<br />&bull; program eligibility information;<br />&bull; information relating to offers, vouchers, pledges, rewards, campaigns, or participation agreements.<br />Some business information may be public or may be displayed through participating Communyti services.</pre>
        <h3>&nbsp;</h3>
        <h3>C. Transaction and Exchange Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">When you use LYGHT POX to access or use eligible value, we may process information including:<br />&bull; transaction amount or value;<br />&bull; type of value presented or used;<br />&bull; voucher information;<br />&bull; token information;<br />&bull; reward information;<br />&bull; pledged value information;<br />&bull; balance information;<br />&bull; transaction date and time;<br />&bull; participating merchant or organization;<br />&bull; transaction status;<br />&bull; authorization or verification information;<br />&bull; redemption history;<br />&bull; transfer history, when applicable;<br />&bull; transaction identifiers;<br />&bull; adjustment, reversal, settlement, or reconciliation information;<br />&bull; records necessary for reporting, fraud prevention, dispute resolution, and program administration.<br />This information is necessary to operate a reliable and accountable point-of-exchange system.</pre>
        <h3>&nbsp;</h3>
        <h3>D. Communications and Support Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">When you contact us, we may collect:<br />&bull; your name;<br />&bull; email address;<br />&bull; telephone number;<br />&bull; account information;<br />&bull; the content of your message;<br />&bull; support requests;<br />&bull; complaint information;<br />&bull; technical information necessary to investigate a problem;<br />&bull; records of communications with our support team.<br />We use this information to respond to questions, provide technical assistance, investigate issues, and improve our services.</pre>
        <h3>&nbsp;</h3>
        <h3>E. Device and Technical Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">When you use the application, certain technical information may be collected automatically by the application, hosting infrastructure, security systems, or authorized service providers.<br />This information may include:<br />&bull; device type;<br />&bull; device model;<br />&bull; operating system and version;<br />&bull; application version;<br />&bull; IP address;<br />&bull; language settings;<br />&bull; time zone;<br />&bull; network information;<br />&bull; session information;<br />&bull; login timestamps;<br />&bull; security events;<br />&bull; diagnostic information;<br />&bull; crash information;<br />&bull; error logs;<br />&bull; application performance information.<br />We use this information to operate, secure, maintain, troubleshoot, and improve the application.</pre>
        <h3>&nbsp;</h3>
        <h3>F. Camera and QR Code Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">When QR code functionality is available, LYGHT POX may request access to your device camera.<br />Camera access may be used to scan participating QR codes or other approved codes necessary to identify, verify, initiate, or complete an eligible interaction.<br />Camera access is subject to device permissions.<br />LYGHT POX does not claim ownership of photographs or personal images stored on your device merely because camera access is enabled. Camera access is intended for application features requiring scanning or image capture.</pre>
        <h3>&nbsp;</h3>
        <h3>G. Location Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">Certain participating programs or future location-based features may use approximate or precise location information when necessary to provide a location-dependent service.<br />Where device location is required, the application will request the applicable device permission.<br />Location information may be used to:<br />&bull; identify participating services or merchants in a relevant area;<br />&bull; support location-dependent program features;<br />&bull; prevent fraud or unauthorized activity;<br />&bull; provide requested location-based functionality.<br />Users can manage location permissions through their device settings. Some location-dependent features may not function properly when permission is denied.</pre>
        <h3>&nbsp;</h3>
        <h3>H. Payment and Financial Transaction Information</h3>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">Some transactions, purchases, payouts, settlements, or connected services may involve a third-party financial institution, payment processor, payment gateway, or other authorized financial service provider.<br />Those providers may collect and process payment information according to their own terms and privacy policies.<br />Depending on the transaction, LYGHT POX may receive limited information necessary to identify and record the status of a transaction, such as:<br />&bull; transaction confirmation;<br />&bull; payment status;<br />&bull; amount;<br />&bull; date;<br />&bull; reference number;<br />&bull; processor transaction identifier.<br />We do not claim to store full payment card information when that information is collected and processed directly by an authorized third-party payment processor.</pre>
        <h2>&nbsp;</h2>
        <h2>4. HOW WE USE INFORMATION</h2>
        <pre class="lazy-load-lastmsg textL" dir="auto"><br />We may use collected information to:<br />&bull; create, authenticate, maintain, and secure user accounts;<br />&bull; provide LYGHT POX features and services;<br />&bull; process authorized point-of-exchange activity;<br />&bull; maintain balances and activity records;<br />&bull; verify participating users, merchants, and organizations;<br />&bull; administer vouchers, rewards, tokens, pledged value, programs, and campaigns;<br />&bull; process redemptions, transfers, adjustments, and related activity;<br />&bull; facilitate authorized interactions between users and participating entities;<br />&bull; prevent fraud, abuse, unauthorized access, and security threats;<br />&bull; investigate suspicious or disputed activity;<br />&bull; provide account notices and service communications;<br />&bull; respond to customer service requests;<br />&bull; troubleshoot application issues;<br />&bull; improve application performance and user experience;<br />&bull; maintain financial, operational, security, and audit records;<br />&bull; enforce agreements and platform rules;<br />&bull; comply with applicable legal and regulatory obligations;<br />&bull; protect LYGHT POX, participating users, merchants, organizations, and communities.<br />We may also use aggregated or de-identified information for reporting, planning, service improvement, and understanding participation patterns, provided such information does not reasonably identify an individual.</pre>
        <h2>&nbsp;</h2>
        <h2>5. HOW INFORMATION MAY BE SHARED</h2>
        <pre class="lazy-load-lastmsg textL" dir="auto"><br />We do not sell personal information as a standalone product.<br />Information may be disclosed only as reasonably necessary for the operation, administration, security, and lawful use of the platform.</pre>
        <h3>&nbsp;</h3>
        <h3>A. Participating Merchants and Organizations</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">When you conduct an eligible transaction or participate in a program, relevant information may be provided to the participating merchant, business, nonprofit organization, sponsor, or program administrator.<br />The information shared is limited to information reasonably necessary to:<br />&bull; verify eligibility;<br />&bull; verify available value;<br />&bull; authorize an exchange;<br />&bull; process a transaction;<br />&bull; confirm a redemption;<br />&bull; administer a program;<br />&bull; resolve a dispute;<br />&bull; produce appropriate program or transaction records.</pre>
        <h3>&nbsp;</h3>
        <h3>B. Network and Program Administrators</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">Where your account participates in a managed community network, campaign, organizational program, or sponsored initiative, authorized administrators may have access to information reasonably necessary to operate that program.<br />Access may include participation records, eligibility status, transaction summaries, campaign activity, and other information relevant to administration of the applicable program.</pre>
        <h3>&nbsp;</h3>
        <h3>C. Service Providers</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">We may use authorized service providers to help provide functions such as:<br />&bull; cloud hosting;<br />&bull; database infrastructure;<br />&bull; authentication;<br />&bull; cybersecurity;<br />&bull; technical support;<br />&bull; application development;<br />&bull; transaction processing;<br />&bull; payment processing;<br />&bull; messaging;<br />&bull; email delivery;<br />&bull; push notifications;<br />&bull; data backup;<br />&bull; fraud prevention;<br />&bull; diagnostics and application performance monitoring.<br />Service providers are authorized to process information only for legitimate service purposes and subject to applicable agreements and legal obligations.</pre>
        <h3>&nbsp;</h3>
        <h3>D. Legal and Safety Requirements</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">Information may be disclosed when reasonably necessary to:<br />&bull; comply with applicable law;<br />&bull; comply with a valid subpoena, court order, or other lawful process;<br />&bull; respond to legitimate government or regulatory requests;<br />&bull; investigate fraud or unlawful activity;<br />&bull; enforce applicable agreements;<br />&bull; protect the rights, property, and safety of users, participating entities, the public, or LYGHT POX.</pre>
        <h3>&nbsp;</h3>
        <h3>E. Organizational Changes</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">Information may be transferred as part of a lawful merger, restructuring, reorganization, financing, transfer of assets, or similar organizational transaction.<br />Any successor responsible for personal information will remain subject to applicable privacy and data protection obligations.</pre>
        <h3>&nbsp;</h3>
        <h3>F. With Your Direction or Consent</h3>
        <pre class="lazy-load-lastmsg textL" dir="auto">We may disclose information when you request, direct, authorize, or consent to the disclosure.</pre>
        <h2>&nbsp;</h2>
        <h2>6. DATA RETENTION</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />We retain information only for as long as reasonably necessary for the purposes described in this Privacy Policy or as required by law, contractual obligations, fraud prevention requirements, dispute resolution needs, financial recordkeeping, security obligations, or legitimate operational needs.<br />Retention periods may differ depending on the type of information.<br />For example:<br />&bull; account information may be retained while an account is active;<br />&bull; transaction records may be retained for accounting, audit, reconciliation, legal, security, or dispute resolution purposes;<br />&bull; support communications may be retained to document and resolve service issues;<br />&bull; security logs may be retained to investigate and prevent unauthorized activity;<br />&bull; legally required records may be retained for the applicable statutory period.<br />When information is no longer reasonably required, it may be deleted, anonymized, aggregated, or securely archived according to applicable procedures.</pre>
        <h2>&nbsp;</h2>
        <h2>7. ACCOUNT DELETION AND DATA REQUESTS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Users may request deletion of an eligible LYGHT POX account and associated personal information.<br />A deletion request may be submitted through the available account management or support process or by contacting LYGHT POX through the contact information provided at the end of this Privacy Policy.<br />To protect users from unauthorized deletion, we may require identity or account verification before processing a request.<br />Following a verified deletion request, we will delete or de-identify personal information associated with the account, subject to information that must be retained for reasons including:<br />&bull; legal obligations;<br />&bull; transaction recordkeeping;<br />&bull; accounting and reconciliation;<br />&bull; fraud prevention;<br />&bull; security investigations;<br />&bull; dispute resolution;<br />&bull; enforcement of agreements;<br />&bull; legitimate program administration requirements.<br />Deleting an account does not necessarily require deletion of transaction records that must be retained for legitimate legal, accounting, security, or operational reasons.</pre>
        <h2>&nbsp;</h2>
        <h2>8. DATA SECURITY</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />We use reasonable administrative, technical, and organizational measures designed to protect information against:<br />&bull; unauthorized access;<br />&bull; unauthorized disclosure;<br />&bull; loss;<br />&bull; misuse;<br />&bull; alteration;<br />&bull; destruction.<br />Security measures may include, where appropriate:<br />&bull; access controls;<br />&bull; authentication procedures;<br />&bull; encrypted communications;<br />&bull; secured hosting infrastructure;<br />&bull; password protection;<br />&bull; role-based access;<br />&bull; system monitoring;<br />&bull; logging;<br />&bull; backups;<br />&bull; security reviews.<br />No application, network, transmission method, or electronic storage system can be guaranteed to be completely secure. Users are responsible for maintaining the confidentiality of their credentials and immediately reporting suspected unauthorized access.</pre>
        <h2>&nbsp;</h2>
        <h2>9. USER RESPONSIBILITIES</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Users should take reasonable steps to protect their account and personal information.<br />Users should:<br />&bull; use strong and unique passwords;<br />&bull; protect account credentials;<br />&bull; avoid sharing verification codes;<br />&bull; log out of devices they do not control;<br />&bull; maintain accurate contact information;<br />&bull; immediately report suspected unauthorized activity.<br />LYGHT POX personnel should never need a user's password in order to provide ordinary support.</pre>

        <h2>&nbsp;</h2>
        <h2>10. CHILDREN'S PRIVACY</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />LYGHT POX is not directed to children under 13 years of age.<br />We do not knowingly collect personal information directly from children under 13 without appropriate authorization or legal basis.<br />If we become aware that personal information from a child under 13 has been collected in a manner inconsistent with applicable law, we will take reasonable steps to delete or appropriately address that information.<br />Participating programs intended for minors must be specifically structured and administered in accordance with applicable legal requirements and appropriate parental, guardian, organizational, or institutional authorization.</pre>

        <h2>&nbsp;</h2>
        <h2>11. THIRD-PARTY SERVICES AND LINKS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />LYGHT POX may connect with or provide access to participating merchants, organizations, service providers, payment processors, websites, or other third-party services.<br />Third-party services operate under their own privacy practices and terms.<br />This Privacy Policy applies to information processed by or on behalf of LYGHT POX. It does not automatically govern the independent privacy practices of third parties.<br />Users should review the privacy policies and terms of third-party services they choose to use.</pre>
        <h2>&nbsp;</h2>
        <h2>12. NOTIFICATIONS AND COMMUNICATIONS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />We may send operational communications related to:<br />&bull; account activity;<br />&bull; security alerts;<br />&bull; transaction confirmations;<br />&bull; redemption activity;<br />&bull; program participation;<br />&bull; changes to the application;<br />&bull; changes to policies;<br />&bull; customer support;<br />&bull; service interruptions;<br />&bull; other important administrative matters.<br />Where optional promotional or marketing communications are offered, users may use available unsubscribe or preference-management mechanisms.<br />Certain operational, transactional, legal, and security messages may still be sent when necessary to provide or protect the service.</pre>

        <h2>&nbsp;</h2>
        <h2>13. YOUR PRIVACY CHOICES AND RIGHTS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Depending upon your location and applicable law, you may have rights relating to your personal information.<br />These rights may include the ability to:<br />&bull; request access to personal information;<br />&bull; request correction of inaccurate information;<br />&bull; request deletion of eligible information;<br />&bull; object to or restrict certain processing;<br />&bull; request information about data practices;<br />&bull; withdraw consent where processing is based on consent;<br />&bull; submit a privacy complaint.<br />We may need to verify your identity before completing certain requests.<br />Some rights are subject to legal limitations and exceptions.</pre>

        <h2>&nbsp;</h2>
        <h2>14. U.S. STATE PRIVACY RIGHTS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Residents of certain U.S. states may have additional privacy rights under applicable state law.<br />Where required by applicable law, eligible users may request information concerning the categories of personal information collected, purposes of processing, categories of recipients, access, correction, deletion, or other legally recognized rights.<br />LYGHT POX does not sell personal information as a standalone product.<br />Requests may be submitted using the contact methods identified in this Privacy Policy.<br />We will not unlawfully discriminate against a user for exercising an applicable privacy right.</pre>

        <h2>&nbsp;</h2>
        <h2>15. INTERNATIONAL USERS</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />LYGHT POX is primarily designed for participating networks, programs, merchants, organizations, and users in markets where the service has been authorized for operation.<br />Users accessing the application from outside the United States acknowledge that information may be processed and stored in the United States or other locations where authorized service providers operate.<br />Where applicable law establishes additional data protection rights, LYGHT POX will take reasonable steps to comply with applicable requirements.</pre>

        <h2>&nbsp;</h2>
        <h2>16. CHANGES TO THIS PRIVACY POLICY</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />We may update this Privacy Policy to reflect:<br />&bull; changes to the application;<br />&bull; new features;<br />&bull; changes in technology;<br />&bull; new program functionality;<br />&bull; legal or regulatory developments;<br />&bull; changes in business or operational practices.<br />When material changes are made, the updated Privacy Policy will include a revised &ldquo;Last Updated&rdquo; date.<br />Where appropriate or legally required, additional notice may be provided through the application, website, account communication, or another reasonable method.<br />Continued use of the application following an update is subject to the current Privacy Policy and applicable law.</pre>

        <h2>&nbsp;</h2>
        <h2>17. CONTACT US</h2>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />Questions, privacy requests, concerns, or account deletion requests regarding LYGHT POX may be directed to:<br /><strong>LYGHT POX</strong><br /><strong>Operated through UNYTI, LLC</strong><br /><strong>Part of the Communyti Ecosystem</strong><br />Website: Communyti.org<br />Phone: <span id="MCPM_1783423206809" class="eventnotifier">423-777-6475</span><br />Privacy and support requests may also be submitted through the official contact methods made available on the Communyti website.<br />Please include sufficient information for us to identify the relevant account or request. Do not send passwords, complete payment card numbers, or other unnecessary sensitive information through ordinary email or contact forms.</pre>
        <pre class="lazy-load lazy-load-lastmsg textL" dir="auto">&nbsp;</pre>
        <h2>LYGHT POX PRIVACY COMMITMENT</h2>
        <pre id="posted-message-container" class="lazy-load lazy-load-lastmsg textL" dir="auto"><br />LYGHT POX exists to help turn participation into practical community activity.<br />That mission depends on trust.<br />We believe personal information should be handled responsibly, exchange activity should be protected, and users should understand how information supports the system they are participating in.<br />Our goal is not simply to create another application.<br />Our goal is to provide a responsible point of exchange where people, businesses, organizations, and communities can participate with greater confidence, transparency, and purpose.<br /><strong>LYGHT POX &mdash; Your Communyti in the palm of your hand.</strong></pre>


    </div>

    <!-- Footer -->
    <div class="footer">
        © {{ date('Y') ?? '2026' }} Lyght POS. All rights reserved.
    </div>

</body>
</html>
