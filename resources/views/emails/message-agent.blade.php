<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Message SIMMo</title>
  <style>
    body        { margin: 0; padding: 0; background: #F4F4F1; font-family: Georgia, serif; color: #2C2C2A; }
    .wrapper    { max-width: 580px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; border: 1px solid #D3D1C7; }
    .header     { background: #1D9E75; padding: 32px 40px; }
    .header h1  { margin: 0; font-size: 22px; color: #FFFFFF; font-weight: 500; letter-spacing: -0.3px; }
    .header p   { margin: 6px 0 0; font-size: 13px; color: #9FE1CB; }
    .body       { padding: 32px 40px; }
    .field      { margin-bottom: 20px; }
    .label      { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #888780; margin-bottom: 4px; }
    .value      { font-size: 15px; color: #2C2C2A; line-height: 1.5; }
    .message-box{ background: #F1EFE8; border-left: 3px solid #1D9E75; border-radius: 0 4px 4px 0; padding: 16px 20px; margin-top: 8px; font-size: 15px; line-height: 1.7; color: #444441; }
    .divider    { border: none; border-top: 1px solid #D3D1C7; margin: 24px 0; }
    .footer     { padding: 20px 40px 28px; background: #F1EFE8; font-size: 12px; color: #888780; line-height: 1.6; }
    .footer a   { color: #0F6E56; text-decoration: none; }
    .badge      { display: inline-block; background: #E1F5EE; color: #0F6E56; font-size: 12px; padding: 3px 10px; border-radius: 20px; border: 1px solid #9FE1CB; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class="header">
      <h1>Nouveau message client</h1>
      <p>Reçu via le formulaire de contact SIMMo</p>
    </div>

    <div class="body">
      <span class="badge">{{ $data['sujet'] }}</span>

      <div style="display: flex; gap: 24px;">
        <div class="field" style="flex: 1;">
          <div class="label">Prénom</div>
          <div class="value">{{ $data['prenom'] }}</div>
        </div>
        <div class="field" style="flex: 1;">
          <div class="label">Nom</div>
          <div class="value">{{ $data['nom'] }}</div>
        </div>
      </div>

      <div class="field">
        <div class="label">Adresse e-mail</div>
        <div class="value">
          <a href="mailto:{{ $data['email'] }}" style="color: #0F6E56;">{{ $data['email'] }}</a>
        </div>
      </div>

      @if (!empty($data['tel']))
      <div class="field">
        <div class="label">Téléphone</div>
        <div class="value">{{ $data['tel'] }}</div>
      </div>
      @endif

      <hr class="divider" />

      <div class="field">
        <div class="label">Message</div>
        <div class="message-box">{{ $data['message'] }}</div>
      </div>
    </div>

    <div class="footer">
      Ce message a été envoyé depuis <a href="#">simmo.cm</a> le {{ now()->format('d/m/Y à H:i') }}.<br>
      Pour répondre, utilisez directement l'adresse : <a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a>
    </div>

  </div>
</body>
</html>