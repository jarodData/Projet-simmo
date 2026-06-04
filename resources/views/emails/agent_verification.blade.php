<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification SIMMo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family : -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background  : #f0f4f8;
            padding     : 40px 16px;
        }

        .wrapper {
            max-width : 580px;
            margin    : 0 auto;
        }

        /* Logo top */
        .top-logo {
            text-align    : center;
            margin-bottom : 24px;
        }

        .top-logo span {
            font-size   : 22px;
            font-weight : 700;
            color       : #1e293b;
            letter-spacing: -0.5px;
        }

        .top-logo span em {
            color           : #2563eb;
            font-style      : normal;
        }

        /* Card */
        .card {
            background    : white;
            border-radius : 16px;
            overflow      : hidden;
            box-shadow    : 0 2px 24px rgba(0,0,0,0.07);
        }

        /* Header */
        .header {
            background  : #1e293b;
            padding     : 40px 32px;
            text-align  : center;
            position    : relative;
            overflow    : hidden;
        }

        .header::before {
            content       : '';
            position      : absolute;
            top           : -60px;
            right         : -60px;
            width         : 200px;
            height        : 200px;
            background    : rgba(37,99,235,0.15);
            border-radius : 50%;
        }

        .header::after {
            content       : '';
            position      : absolute;
            bottom        : -40px;
            left          : -40px;
            width         : 150px;
            height        : 150px;
            background    : rgba(14,165,233,0.1);
            border-radius : 50%;
        }

        .header-icon {
            width         : 64px;
            height        : 64px;
            background    : rgba(37,99,235,0.2);
            border-radius : 50%;
            display       : flex;
            align-items   : center;
            justify-content: center;
            margin        : 0 auto 16px;
            position      : relative;
            z-index       : 1;
            font-size     : 28px;
        }

        .header h1 {
            color      : white;
            font-size  : 24px;
            font-weight: 700;
            position   : relative;
            z-index    : 1;
            margin-bottom: 6px;
        }

        .header p {
            color    : rgba(255,255,255,0.55);
            font-size: 14px;
            position : relative;
            z-index  : 1;
        }

        /* Body */
        .body {
            padding : 40px 36px;
        }

        .greeting {
            font-size   : 20px;
            font-weight : 700;
            color       : #0f172a;
            margin-bottom: 12px;
        }

        .body p {
            color       : #64748b;
            line-height : 1.75;
            font-size   : 15px;
            margin-bottom: 16px;
        }

        /* Steps */
        .steps {
            display       : flex;
            flex-direction: column;
            gap           : 12px;
            margin        : 28px 0;
        }

        .step {
            display    : flex;
            align-items: center;
            gap        : 14px;
            padding    : 14px 16px;
            background : #f8fafc;
            border-radius: 10px;
            border     : 1px solid #e2e8f0;
        }

        .step-num {
            width         : 32px;
            height        : 32px;
            background    : #2563eb;
            color         : white;
            border-radius : 50%;
            display       : flex;
            align-items   : center;
            justify-content: center;
            font-weight   : 700;
            font-size     : 14px;
            flex-shrink   : 0;
        }

        .step-text {
            font-size  : 14px;
            color      : #475569;
            font-weight: 500;
        }

        /* CTA Button */
        .btn-wrapper {
            text-align : center;
            margin     : 32px 0 24px;
        }

        .btn {
            display         : inline-block;
            background      : #2563eb;
            color           : white !important;
            padding         : 16px 40px;
            border-radius   : 10px;
            text-decoration : none;
            font-weight     : 700;
            font-size       : 16px;
            letter-spacing  : 0.3px;
            transition      : background 0.2s;
        }

        .btn:hover { background: #1d4ed8; }

        /* Info box */
        .info-box {
            background    : #eff6ff;
            border-left   : 3px solid #2563eb;
            padding       : 14px 16px;
            border-radius : 0 8px 8px 0;
            margin        : 24px 0;
            font-size     : 13px;
            color         : #1e40af;
            line-height   : 1.6;
        }

        .info-box strong { color: #1e3a8a; }

        /* Lien texte */
        .link-fallback {
            background    : #f8fafc;
            border        : 1px solid #e2e8f0;
            border-radius : 8px;
            padding       : 14px 16px;
            margin        : 20px 0;
        }

        .link-fallback p {
            font-size    : 12px;
            color        : #94a3b8;
            margin-bottom: 6px !important;
        }

        .link-fallback a {
            word-break : break-all;
            color      : #2563eb;
            font-size  : 12px;
        }

        /* Divider */
        .divider {
            height     : 1px;
            background : #f1f5f9;
            margin     : 28px 0;
        }

        /* Login section */
        .login-section {
            text-align    : center;
            padding       : 20px;
            background    : #f8fafc;
            border-radius : 10px;
            border        : 1px solid #e2e8f0;
        }

        .login-section p {
            font-size    : 14px;
            color        : #64748b;
            margin-bottom: 12px !important;
        }

        .btn-login {
            display         : inline-block;
            background      : white;
            color           : #2563eb !important;
            padding         : 10px 28px;
            border-radius   : 8px;
            text-decoration : none;
            font-weight     : 600;
            font-size       : 14px;
            border          : 2px solid #2563eb;
        }

        /* Footer */
        .footer {
            padding    : 24px 32px;
            text-align : center;
            border-top : 1px solid #f1f5f9;
        }

        .footer p {
            color     : #94a3b8;
            font-size : 12px;
            line-height: 1.6;
        }

        .footer a {
            color           : #2563eb;
            text-decoration : none;
        }

        .social-links {
            display        : flex;
            justify-content: center;
            gap            : 16px;
            margin-top     : 12px;
        }

        .social-links a {
            color     : #94a3b8;
            font-size : 12px;
            text-decoration: none;
        }

        .social-links a:hover { color: #2563eb; }

        .badge-verified {
            display       : inline-flex;
            align-items   : center;
            gap           : 6px;
            background    : #f0fdf4;
            color         : #16a34a;
            padding       : 6px 14px;
            border-radius : 20px;
            font-size     : 13px;
            font-weight   : 600;
            margin-bottom : 20px;
            border        : 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <!-- Logo -->
        <div class="top-logo">
            <span>SIMm<em>o</em></span>
        </div>

        <div class="card">

            <!-- Header -->
            <div class="header">
                <div class="header-icon">🏠</div>
                <h1>Vérifiez votre compte</h1>
                <p>Solution Immobilière Intelligente au Cameroun</p>
            </div>

            <!-- Body -->
            <div class="body">

                <div class="badge-verified">
                    ✅ Inscription réussie !
                </div>

                <p class="greeting">Bonjour {{ $utilisateur->prenom }} ! 👋</p>

                <p>
                    Merci de rejoindre <strong style="color:#0f172a">SIMMo</strong> !
                    Votre compte a été créé avec succès. Il ne vous reste
                    plus qu'une étape pour accéder à votre espace personnel.
                </p>

                <!-- Étapes -->
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-text">Cliquez sur le bouton ci-dessous pour vérifier votre email</div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-text">Connectez-vous avec votre email et mot de passe</div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-text">Trouvez votre logement idéal grâce à notre IA</div>
                    </div>
                </div>

                <!-- Bouton principal -->
                <div class="btn-wrapper">
                    <a href="{{ url('/api/auth/utilisateur/verify/' . $token) }}" class="btn">
                        ✅ Vérifier mon compte
                    </a>
                </div>

                <!-- Info box -->
                <div class="info-box">
                    <strong>⏰ Important :</strong> Ce lien expire dans
                    <strong>24 heures</strong>. Si vous n'avez pas créé
                    de compte sur SIMMo, ignorez cet email.
                </div>

                <!-- Lien fallback -->
                <div class="link-fallback">
                    <p>Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</p>
                    <a href="{{ url('/api/auth/utilisateur/verify/' . $token) }}">
                        {{ url('/api/auth/utilisateur/verify/' . $token) }}
                    </a>
                </div>

                <div class="divider"></div>

                <!-- Section login -->
                <div class="login-section">
                    <p>
                        Après avoir vérifié votre compte, connectez-vous
                        pour accéder à toutes les fonctionnalités SIMMo.
                    </p>
                    <a href="{{ url('/login.html') }}" class="btn-login">
                        🔑 Se connecter à SIMMo
                    </a>
                </div>

            </div>

            <!-- Footer -->
            <div class="footer">
                <p>
                    Cet email a été envoyé par <strong>SIMMo</strong> à
                    <strong>{{ $utilisateur->email }}</strong><br>
                    Si vous avez des questions, contactez-nous à
                    <a href="mailto:support@simmo.cm">support@simmo.cm</a>
                </p>
                <div class="social-links">
                    <a href="#">Accueil</a>
                    <a href="#">Annonces</a>
                    <a href="#">Se désabonner</a>
                </div>
                <p style="margin-top:12px">
                    © 2026 SIMMo — Solution Immobilière Cameroun
                </p>
            </div>

        </div>
    </div>
</body>
</html>
