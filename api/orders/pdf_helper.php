<?php
// Helper to generate Order PDF content consistently across endpoints (Enviar/Facturar)
// Mirrors the layout used by generar_pdf.php (Imprimir) without altering email logic.

// Ensure FPDF is available
if (!class_exists('FPDF')) {
	$fpdfPath = __DIR__ . '/../../fpdf186/fpdf.php';
	if (file_exists($fpdfPath)) {
		require_once $fpdfPath;
	} else {
		// Let callers handle missing library errors if needed
		throw new Exception('Librería FPDF no encontrada en fpdf186/fpdf.php');
	}
}

class PDF_ORDER extends FPDF {
	function Header() {}
	function Footer() {}
}

// Safely decode UTF-8 for classic FPDF (ISO-8859-1)
function _pdf_txt($s) {
	// Guard nulls and arrays/objects
	if ($s === null) return '';
	if (is_array($s) || is_object($s)) return '';
	$str = (string)$s;
	// Convert to ISO-8859-1-compatible text
	return utf8_decode($str);
}

// Determine if status corresponds to a quote (cotización)
function _is_quote_status($status) {
	if (!is_string($status)) return false;
	return stripos($status, 'cotiz') !== false; // handles "Cotización" / "Cotizacion"
}

// Numeric formatter
function _pdf_money($n) { return '$' . number_format((float)$n, 2); }

// Validate image path and type to avoid FPDF fatal errors
function _pdf_is_valid_image($path) {
	if (!@is_file($path) || !@is_readable($path)) return false;
	if (@filesize($path) <= 0) return false;
	$info = @getimagesize($path);
	if ($info === false) return false;
	$mime = $info['mime'] ?? '';
	// Allow JPEG and PNG always; allow GIF only if GD is available
	if ($mime === 'image/jpeg' || $mime === 'image/png') return true;
	if ($mime === 'image/gif' && extension_loaded('gd')) return true;
	return false;
}

function _pdf_log($text) {
	$logsDir = __DIR__ . '/../logs';
	if (!@is_dir($logsDir)) @mkdir($logsDir, 0755, true);
	$file = $logsDir . '/pdf_helper.log';
	$entry = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
	@file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

// Download a remote image to a temporary local file (prefer PNG/JPEG; GIF only if GD)
function _pdf_fetch_to_temp($url) {
	if (!preg_match('#^https?://#i', $url)) return null;
	$pathPart = parse_url($url, PHP_URL_PATH) ?? '';
	$ext = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));
	$typeOk = in_array($ext, ['png','jpg','jpeg','gif'], true);
	if (!$typeOk) {
		_pdf_log('Fetch skip: unsupported ext for URL ' . $url);
		return null;
	}
	if ($ext === 'gif' && !extension_loaded('gd')) {
		_pdf_log('Fetch skip: GIF without GD for URL ' . $url);
		return null;
	}
	$ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
	$data = @file_get_contents($url, false, $ctx);
	if ($data === false || strlen($data) === 0) {
		_pdf_log('Fetch failed for URL ' . $url);
		return null;
	}
	$tmp = tempnam(sys_get_temp_dir(), 'logo_');
	// Ensure extension preserved for FPDF type detection
	$tmpWithExt = $tmp . '.' . $ext;
	@unlink($tmp);
	if (@file_put_contents($tmpWithExt, $data) === false) {
		_pdf_log('Failed to write temp file for URL ' . $url);
		return null;
	}
	// Validate the written file
	if (!_pdf_is_valid_image($tmpWithExt)) {
		_pdf_log('Temp file invalid after fetch: ' . $tmpWithExt);
		@unlink($tmpWithExt);
		return null;
	}
	_pdf_log('Fetched logo to temp: ' . $tmpWithExt);
	return $tmpWithExt;
}

/**
 * Generate order PDF content as a string (for email attachments)
 * @param array $orderData Shape:
 *   - numericId (int|string)
 *   - status (string)
 *   - client: [name, address, cel]
 *   - vehicle: [brand, plates, year, km]
 *   - items: array of rows with keys: description, qty, price (price is line total)
 *   - subtotal, iva, total (numbers)
 *   - observations (string)
 *   - logoUrl (optional)
 * @return string PDF bytes
 */
function generateOrderPDF($orderData) {
	$pdf = new PDF_ORDER('P', 'mm', 'Letter');
	$pdf->AddPage();
	$pdf->SetFont('Arial', '', 10);
	$pdf->SetAutoPageBreak(true, 10);

	// Header (text-centered; optional logo skipped unless local file)
	$status = $orderData['status'] ?? '';
	$isQuote = _is_quote_status($status);

	// Try rendering logo only if it's a local readable file to avoid FPDF fatal errors with remote URLs
	// Choose a logo file: prefer PNG/JPG; allow GIF only if GD is loaded
	$logoCandidates = [];
	$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
	if (!empty($orderData['logoUrl']) && is_string($orderData['logoUrl'])) {
		$logo = $orderData['logoUrl'];
		// If it's an http(s) URL, try it directly first (like Imprimir)
		if (preg_match('#^https?://#i', $logo)) {
			$logoCandidates[] = $logo; // direct URL
		} else {
			// As-is (may work if cwd matches project root)
			$logoCandidates[] = $logo;
			// Relative to helper (../../ => project root)
			$logoCandidates[] = __DIR__ . '/../../' . ltrim($logo, '/\\');
			// Relative to web document root
			if ($docRoot !== '') {
				$logoCandidates[] = $docRoot . '/' . ltrim($logo, '/\\');
			}
		}
	}
	// Common fallbacks (project structure)
	$fallbacks = [
		'assets/images/err.png',
		'assets/images/err.jpg',
		'assets/images/err.jpeg',
		'assets/images/err.gif',
	];
	foreach ($fallbacks as $fb) {
		$logoCandidates[] = __DIR__ . '/../../' . $fb; // relative to helper
		if ($docRoot !== '') $logoCandidates[] = $docRoot . '/' . $fb; // relative to web root
	}

	$logoEmbedded = false;
	foreach ($logoCandidates as $candidate) {
		$isUrl = is_string($candidate) && preg_match('#^https?://#i', $candidate);
		if ($isUrl) {
			// For URLs, mimic Imprimir: try direct embed (avoid GIF without GD)
			$ext = strtolower(pathinfo(parse_url($candidate, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
			$type = null;
			if (in_array($ext, ['jpg', 'jpeg'], true)) $type = 'JPEG';
			elseif ($ext === 'png') $type = 'PNG';
			elseif ($ext === 'gif') $type = 'GIF';
			if ($type === 'GIF' && !extension_loaded('gd')) {
				_pdf_log('Skip remote GIF without GD: ' . $candidate);
				continue;
			}

			// Final fallback: if a URL was provided or known default URL works better, try fetching to temp
			if (!$logoEmbedded) {
				$defaultUrl = 'https://errautomotriz.com/assets/images/err.png';
				$tryUrls = [];
				if (!empty($orderData['logoUrl']) && preg_match('#^https?://#i', (string)$orderData['logoUrl'])) {
					$tryUrls[] = $orderData['logoUrl'];
				}
				$tryUrls[] = $defaultUrl;
				foreach ($tryUrls as $u) {
					$tmp = _pdf_fetch_to_temp($u);
					if ($tmp) {
						try {
							// Detect type via ext
							$ext = strtolower(pathinfo($tmp, PATHINFO_EXTENSION));
							$type = null;
							if (in_array($ext, ['jpg','jpeg'], true)) $type = 'JPEG';
							elseif ($ext === 'png') $type = 'PNG';
							elseif ($ext === 'gif') $type = 'GIF';
							$type ? $pdf->Image($tmp, 10, 8, 33, 0, $type) : $pdf->Image($tmp, 10, 8, 33);
							$logoEmbedded = true;
							_pdf_log('Logo embedded from temp: ' . $tmp . ' (origin ' . $u . ')');
						} catch (Exception $e) {
							_pdf_log('Embed from temp failed: ' . $tmp . ' -> ' . $e->getMessage());
						}
						// Clean up temp file
						@unlink($tmp);
						if ($logoEmbedded) break;
					}
				}
			}
			try {
				$type ? $pdf->Image($candidate, 10, 8, 33, 0, $type) : $pdf->Image($candidate, 10, 8, 33);
				$logoEmbedded = true;
				_pdf_log('Logo embedded from URL: ' . $candidate);
			} catch (Exception $e) {
				_pdf_log('Logo URL embed failed: ' . $candidate . ' -> ' . $e->getMessage());
			}
			if ($logoEmbedded) break;
			// If URL failed, continue to next candidate
			continue;
		}

		if (_pdf_is_valid_image($candidate)) {
			// Determine type explicitly
			$ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
			$type = null;
			if (in_array($ext, ['jpg', 'jpeg'], true)) $type = 'JPEG';
			elseif ($ext === 'png') $type = 'PNG';
			elseif ($ext === 'gif') $type = 'GIF';

			// Log basic info
			$info = @getimagesize($candidate);
			$mime = $info['mime'] ?? 'unknown';
			_pdf_log('Logo candidate OK: ' . $candidate . ' | ext=' . $ext . ' | mime=' . $mime . ' | type=' . ($type ?? 'auto'));

			try {
				// Try with explicit type first (helps when extension/mime tricks FPDF)
				if ($type) {
					$pdf->Image($candidate, 10, 8, 33, 0, $type);
				} else {
					$pdf->Image($candidate, 10, 8, 33);
				}
				$logoEmbedded = true;
				_pdf_log('Logo embedded: ' . $candidate);
			} catch (Exception $e) {
				_pdf_log('Logo embed failed for ' . $candidate . ' -> ' . $e->getMessage());
			}
			if ($logoEmbedded) break;
		} else {
			_pdf_log('Logo candidate invalid or not found: ' . $candidate);
		}
	}

	$pdf->SetFont('Arial','B',16);
	$pdf->Cell(0, 7, 'ERR Automotriz', 0, 1, 'C');
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0, 7, _pdf_txt($isQuote ? 'COTIZACIÓN' : 'ORDEN DE SERVICIO'), 0, 1, 'C');
	$pdf->SetFont('Arial','B',12);
	$label = $isQuote ? 'Cotización: ' : 'No. de Orden: ';
	$pdf->Cell(190, 8, _pdf_txt($label) . _pdf_txt($orderData['numericId'] ?? ''), 1, 1, 'R');
	$pdf->Ln(5);

	// Client and Vehicle block aligned to printing template
	$client = $orderData['client'] ?? [];
	$vehicle = $orderData['vehicle'] ?? [];
	$createdAt = $orderData['createdAt'] ?? '';
	if ($createdAt) {
		// Normalize date to d/m/Y if parseable
		$ts = @strtotime($createdAt);
		if ($ts) $createdAt = date('d/m/Y', $ts);
	}
	// Label shading similar to HTML th background
	$pdf->SetFillColor(234, 234, 234);
	$labelW = 35; // mm
	$valHalfW = 60; // per half-row: 35 + 60 = 95
	$valFullW = 190 - $labelW; // 155

	// FECHAS (two pairs)
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('FECHA ENTRADA:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($createdAt ?: ''), 1, 0, 'L');
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('FECHA SALIDA:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt(''), 1, 1, 'L');

	// CLIENTE (full width)
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('CLIENTE:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valFullW, 6, _pdf_txt($client['name'] ?? ''), 1, 1, 'L');

	// DIRECCION (full width)
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('DIRECCION:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valFullW, 6, _pdf_txt($client['address'] ?? ''), 1, 1, 'L');

	// RFC / CEL
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('R.F.C:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($client['rfc'] ?? ''), 1, 0, 'L');
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('CEL:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($client['cel'] ?? ''), 1, 1, 'L');

	// MARCA/MODELO / PLACAS
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('MARCA/MODELO:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($vehicle['brand'] ?? ''), 1, 0, 'L');
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('PLACAS:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($vehicle['plates'] ?? ''), 1, 1, 'L');

	// MEDIDOR GAS / KM
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('MEDIDOR GAS:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($vehicle['gasLevel'] ?? ''), 1, 0, 'L');
	$pdf->SetFont('Arial','B', 9);
	$pdf->Cell($labelW, 6, _pdf_txt('KM:'), 1, 0, 'L', true);
	$pdf->SetFont('Arial','', 9);
	$pdf->Cell($valHalfW, 6, _pdf_txt($vehicle['km'] ?? ''), 1, 1, 'L');

	$pdf->Ln(5);

	// Items table (same layout as generar_pdf.php)
	$pdf->SetFont('Arial','B',10);
	$pdf->SetFillColor(234, 234, 234);
	$pdf->Cell(20, 7, _pdf_txt('CANT.'), 1, 0, 'C', true);
	$pdf->Cell(130, 7, _pdf_txt('DESCRIPCIÓN'), 1, 0, 'C', true);
	$pdf->Cell(40, 7, _pdf_txt('IMPORTE'), 1, 1, 'C', true);

	$pdf->SetFont('Arial','',10);
	$items = $orderData['items'] ?? [];
	foreach ($items as $item) {
		$qty = is_array($item) ? ($item['qty'] ?? 0) : ($item->qty ?? 0);
		$desc = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
		// In DB, price often holds the line total (qty * unit)
		$linePrice = is_array($item) ? ($item['price'] ?? 0) : ($item->price ?? 0);

		$pdf->Cell(20, 7, _pdf_txt($qty), 1, 0, 'C');
		$pdf->Cell(130, 7, _pdf_txt($desc), 1, 0, 'L');
		$pdf->Cell(40, 7, _pdf_txt(_pdf_money($linePrice)), 1, 1, 'R');
	}
	$pdf->Ln(5);

	// Observations
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(0, 7, _pdf_txt('Observaciones:'), 'LTR', 1);
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell(190, 5, _pdf_txt($orderData['observations'] ?? ''), 'LBR');

	// Totals
	$subtotal = $orderData['subtotal'] ?? 0;
	$iva = $orderData['iva'] ?? 0;
	$total = $orderData['total'] ?? 0;

	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(130, 7, '', 0, 0);
	$pdf->Cell(30, 7, _pdf_txt('SUBTOTAL'), 1, 0, 'R');
	$pdf->Cell(30, 7, _pdf_txt(_pdf_money($subtotal)), 1, 1, 'R');

	$ivaApplied = isset($orderData['ivaApplied']) ? (bool)$orderData['ivaApplied'] : null;
	$showIva = $ivaApplied !== null ? $ivaApplied : ((float)$iva > 0);
	if ($showIva) {
		$pdf->Cell(130, 7, '', 0, 0);
		$pdf->Cell(30, 7, _pdf_txt('IVA'), 1, 0, 'R');
		$pdf->Cell(30, 7, _pdf_txt(_pdf_money($iva)), 1, 1, 'R');
	}

	$pdf->Cell(130, 7, '', 0, 0);
	$pdf->Cell(30, 7, _pdf_txt('TOTAL'), 1, 0, 'R');
	$pdf->Cell(30, 7, _pdf_txt(_pdf_money($total)), 1, 1, 'R');

	// IVA footer note like printing template
	$pdf->Ln(3);
	$pdf->SetFont('Arial', 'B', 9);
	$ivaApplied = isset($orderData['ivaApplied']) ? (bool)$orderData['ivaApplied'] : ((float)($orderData['iva'] ?? 0) > 0);
	$ivaNote = $ivaApplied ? 'LOS PRECIOS INCLUYEN IVA' : 'LOS PRECIOS NO INCLUYEN IVA';
	$pdf->Cell(0, 5, _pdf_txt($ivaNote), 0, 1, 'L');

	return $pdf->Output('S');
}

?>
