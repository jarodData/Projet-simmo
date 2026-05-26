<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Agent SIMMo</title>
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
            background : linear-gradient(135deg, #16a34a, #0ea5e9);
            padding    : 32px;
            text-align : center;
            color      : white;
        }
        .header h1 { margin: 0; font-size: 28px; }
        .header p  { margin: 8px 0 0; opacity: 0.85; }
        .body      { padding: 40px 32px; }
        .body h2   { color: #1e293b; font-size: 22px; }
        .body p    { color: #64748b; line-height: 1.7; }
        .btn {
            display         : inline-block;
            background      : #16a34a;
            color           : white;
            padding         : 14px 32px;
            border-radius   : 8px;
            text-decoration : none;
            font-weight     : bold;
            font-size       : 16px;
            margin          : 24px 0;
        }
        .etape {
            display         : flex;
            align-items     : flex-start;
            gap             : 16px;
            margin-bottom   : 16px;
            padding         : 16px;
            background      : #f8fafc;
            border-radius   : 8px;
        }
        .etape-num {
            background      : #16a34a;
            color           : white;
            width           : 28px;
            height          : 28px;
            border-radius   : 50%;
            display         : flex;
            align-items     : center;
            justify-content : center;
            font-weight     : bold;
            flex-shrink     : 0;
        }
        .footer {
            background  : #f8fafc;
            padding     : 20px 32px;
            text-align  : center;
            color       : #94a3b8;
            font-size   : 12px;
            border-top  : 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SIMMo - Espace Agent</h1>
            <p>Bienvenue sur la plateforme</p>
        </div>
        <div class="body">
            <h2>Bonjour {{ $agent->prenom }} !</h2>
            <p>
                Votre demande de compte agent a bien ete recue.
                Voici les prochaines etapes :
            </p>

            <div class="etape">
                <div class="etape-num">1</div>
                <div>
                    <strong>Verifier votre email</strong><br>
                    <span style="color:#64748b;font-size:14px">
                        Cliquez sur le bouton ci-dessous
                    </span>
                </div>
            </div>
            <div class="etape">
                <div class="etape-num">2</div>
                <div>
                    <strong>Validation de votre CNI</strong><br>
                    <span style="color:#64748b;font-size:14px">
                        Notre equipe verifiera votre document sous 24h
                    </span>
                </div>
            </div>
            <div class="etape">
                <div class="etape-num">3</div>
                <div>
                    <strong>Activation et 1 mois gratuit</strong><br>
                    <span style="color:#64748b;font-size:14px">
                        Commencez a publier vos annonces gratuitement
                    </span>
                </div>
            </div>

            <div style="text-align:center">
                <a href="{{ url('/api/auth/agent/verify/' . $token) }}"
                    class="btn">
                    Verifier mon email
                </a>
            </div>

            <p style="color:#94a3b8;font-size:13px">
                Ce lien expire dans 24 heures.
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