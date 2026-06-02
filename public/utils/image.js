// utils/image.js  — helper à réutiliser partout

export function imageAvecFallback(src, titre = "SIMMo") {
    if (src && src !== "null" && src !== "undefined") {
        return src;
    }
    // Fallback : image data URI générée localement, sans réseau
    return genererPlaceholder(titre);
}

function genererPlaceholder(texte) {
    const canvas  = document.createElement("canvas");
    canvas.width  = 400;
    canvas.height = 250;
    const ctx     = canvas.getContext("2d");

    // Fond
    ctx.fillStyle = "#e2e8f0";
    ctx.fillRect(0, 0, 400, 250);

    // Texte centré
    ctx.fillStyle    = "#64748b";
    ctx.font         = "bold 20px sans-serif";
    ctx.textAlign    = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(texte.substring(0, 20), 200, 125);

    return canvas.toDataURL("image/png");
}