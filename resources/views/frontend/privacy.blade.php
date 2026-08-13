@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('description', 'How this website collects, uses, and protects your personal information, including your rights under GDPR and other privacy laws.')

@php
    $siteHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'this website';
    $privacyEmail = setting('contact_email') ?: config('mail.from.address', 'privacy@' . $siteHost);
    $effectiveDate = 'August 13, 2026';
@endphp

@section('content')
<div class="bg-midnight text-soft">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-4xl font-bold text-soft-light mb-3">Privacy Policy</h1>
        <p class="text-soft/70">Last updated: {{ $effectiveDate }}</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="space-y-10 text-gray-700 dark:text-gray-300 leading-relaxed">

        <section>
            <p>
                This Privacy Policy explains how {{ $siteHost }} ("we", "us", or "our") collects, uses, and
                protects your personal information when you visit our website. We are committed to handling your
                data responsibly and in line with applicable privacy laws, including the EU General Data
                Protection Regulation (GDPR) and comparable regulations in other regions. By using this website
                you agree to the practices described here.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">1. Information We Collect</h2>
            <p class="mb-3">We collect only what we need to operate the site and respond to you:</p>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Information you provide.</strong> When you submit the contact form or book a demo, we collect the details you enter, such as your name, email address, and message.</li>
                <li><strong>Usage and analytics data.</strong> We collect anonymized or pseudonymized information about how visitors use the site, such as pages viewed, approximate location, referrer, device, and browser type.</li>
                <li><strong>Technical log data.</strong> Our servers automatically record standard information such as IP address, timestamps, and requested pages for security and reliability.</li>
                <li><strong>Cookies.</strong> We use a small number of cookies and similar technologies. See the Cookies section below.</li>
            </ul>
            <p class="mt-3">We do not knowingly collect sensitive personal data, and we do not require you to create an account to browse the site.</p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">2. How We Use Your Information</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>To respond to your enquiries and demo requests.</li>
                <li>To operate, maintain, and secure the website.</li>
                <li>To understand how the site is used so we can improve content and performance.</li>
                <li>To comply with legal obligations and to protect against fraud or abuse.</li>
            </ul>
            <p class="mt-3">
                Our legal bases for processing include your consent, our legitimate interest in running and
                improving the site, and, where relevant, the steps needed to respond to your requests.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">3. Cookies and Similar Technologies</h2>
            <p>
                Cookies are small files stored on your device. We use essential cookies that are required for the
                site to function, and analytics cookies that help us measure usage. You can control or delete
                cookies through your browser settings. Blocking some cookies may affect how parts of the site work.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">4. Third-Party Services</h2>
            <p class="mb-3">We rely on a limited set of trusted service providers to run the site. These may include:</p>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Analytics providers</strong>, to measure and improve site usage.</li>
                <li><strong>A payment processor</strong>, if you purchase a product, which handles your payment details directly. We do not store your full card details.</li>
                <li><strong>Email delivery</strong>, to send you responses and confirmations.</li>
                <li><strong>Social platforms.</strong> We publish our own articles to our own social media accounts. This does not collect data about you, and social share or follow links are governed by the privacy policies of those platforms.</li>
            </ul>
            <p class="mt-3">Each provider processes data only as needed to deliver its service, under its own privacy terms.</p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">5. How We Share Information</h2>
            <p>
                We do not sell or rent your personal information. We share it only with the service providers
                described above, when required by law or valid legal process, or to protect our rights, users,
                and the security of the site.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">6. Data Retention</h2>
            <p>
                We keep personal information only for as long as it is needed for the purposes described in this
                policy, or as required by law. Contact and demo enquiries are retained while we assist you and for
                a reasonable period afterwards, then deleted or anonymized. Analytics data is retained in
                aggregated or pseudonymized form.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">7. Your Rights</h2>
            <p class="mb-3">
                Depending on where you live, you may have some or all of the following rights over your personal data:
            </p>
            <ul class="list-disc pl-6 space-y-2">
                <li>Access a copy of the personal data we hold about you.</li>
                <li>Ask us to correct inaccurate or incomplete data.</li>
                <li>Ask us to delete your data ("right to be forgotten").</li>
                <li>Object to or restrict certain processing.</li>
                <li>Request a portable copy of the data you provided.</li>
                <li>Withdraw consent at any time, without affecting processing already carried out.</li>
            </ul>
            <p class="mt-3">
                To exercise any of these rights, contact us using the details below. We will respond within the
                timeframe required by applicable law. You also have the right to complain to your local data
                protection authority.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">8. International Data Transfers</h2>
            <p>
                This website serves a global audience, and your information may be processed in countries other
                than your own. Where data is transferred across borders, we take reasonable steps to ensure it
                remains protected in line with this policy and applicable law.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">9. Children's Privacy</h2>
            <p>
                This website is not directed at children, and we do not knowingly collect personal information
                from children. If you believe a child has provided us with personal data, please contact us and
                we will delete it.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">10. Security</h2>
            <p>
                We use reasonable technical and organizational measures to protect your information, including
                encryption of sensitive credentials and access controls. No method of transmission or storage is
                completely secure, so we cannot guarantee absolute security.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">11. Changes to This Policy</h2>
            <p>
                We may update this Privacy Policy from time to time. When we do, we will revise the "Last updated"
                date at the top of this page. We encourage you to review this page periodically.
            </p>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-4">12. Contact Us</h2>
            <p>
                If you have questions about this policy or wish to exercise your privacy rights, please reach out
                through our <a href="{{ route('contact') }}" class="text-teal hover:underline font-medium">contact page</a>
                or by email at
                <a href="mailto:{{ $privacyEmail }}" class="text-teal hover:underline font-medium">{{ $privacyEmail }}</a>.
            </p>
        </section>

    </div>
</div>
@endsection
