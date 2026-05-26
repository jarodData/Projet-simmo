<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification SIMMo</title>
    <style>
        body {
            font-family : Arial, sans-serif;
            background  : #f8fafc;
            margin      : 0;
            padding     : 0;
        }
        .container {
            max-width     : 600px;
            margin        : 40px auto;
            background    : white;
            border-radius : 12px;
            overflow      : hidden;
            box-shadow    : 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background : linear-gradient(135deg, #2563eb, #0ea5e9);
            padding    : 32px;
            text-align : center;
            color      : white;
        }
        .header h1 {
            margin    : 0;
            font-size : 28px;
        }
        .header p {
            margin  : 8px 0 0;
            opacity : 0.85;
        }
        .body {
            padding : 40px 32px;
        }
        .body h2 {
            color     : #1e293b;
            font-size : 22px;
        }
        .body p {
            color       : #64748b;
            line-height : 1.7;
        }
        .btn {
            display       : inline-block;
            background    : #2563eb;
            color         : white;
            padding       : 14px 32px;
            border-radius : 8px;
            text-decoration : none;
            font-weight   : bold;
            font-size     : 16px;
            margin        : 24px 0;
        }
        .footer {
            background  : #f8fafc;
            padding     : 20px 32px;
            text-align  : center;
            color       : #94a3b8;
            font-size   : 12px;
            border-top  : 1px solid #e2e8f0;
        }
        .info-box {
            background    : #eff6ff;
            border-left   : 4px solid #2563eb;
            padding       : 16px;
            border-radius : 4px;
            margin        : 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SIMMo</h1>
            <p>Solution Immobiliere Intelligente</p>
        </div>
        <div class="body">
            <h2>Bonjour {{ $utilisateur->prenom }} !</h2>
            <p>
                Merci de vous etre inscrit sur <strong>SIMMo</strong>.
                Votre compte a ete cree avec succes.
            </p>
            <p>
                Pour activer votre compte et commencer a chercher
                votre logement ideal, cliquez sur le bouton ci-dessous :
            </p>

            <div style="text-align:center">
                <a href="{{ url('/api/auth/utilisateur/verify/' . $token) }}"
                    class="btn">
                    Verifier mon compte
                </a>
            </div>

            <div class="info-box">
                <strong>Important :</strong> Ce lien expire dans
                24 heures. Si vous n'avez pas cree de compte sur
                SIMMo, ignorez cet email.
            </div>

            <p>
                Si le bouton ne fonctionne pas, copiez ce lien
                dans votre navigateur :
            </p>
            <p style="word-break:break-all;color:#2563eb;font-size:13px">
                {{ url('/api/auth/utilisateur/verify/' . $token) }}
            </p>
        </div>
        <div class="footer">
            <p>
                Cet email a ete envoye par SIMMo.<br>
                2026 SIMMo - Solution Immobiliere Cameroun
            </p>
        </div>
    </div>
</body>
</html>