/**
 * Double authentification : afficher le QR code d'enrôlement
 *
 * Rend en SVG l'URI otpauth:// portée par l'attribut data-otpauth,
 * avec la librairie qrcode-generator (javascript/qrcode.js).
 */
(function () {
	if (typeof qrcode !== 'function') {
		return;
	}
	document.querySelectorAll('.twofa_qrcode[data-otpauth]').forEach(function (el) {
		var qr = qrcode(0, 'M');
		qr.addData(el.dataset.otpauth);
		qr.make();
		el.innerHTML = qr.createSvgTag({cellSize: 4, margin: 16, alt: el.dataset.alt || ''});
	});
})();
