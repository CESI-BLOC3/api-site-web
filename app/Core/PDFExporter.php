<?php namespace App\Core;
class PDFExporter {
  private static function esc(string $s): string { return str_replace(['\\','(',')',"\r","\n"],['\\\\','\(','\)', '', ''],$s); }
  private static function jpegSize(string $bin): array {
    $i = 2; $len = strlen($bin);
    while ($i+9 < $len) {
      if (ord($bin[$i]) != 0xFF) { $i++; continue; }
      $marker = ord($bin[$i+1]); $i += 2;
      if ($i+1 >= $len) break;
      $seglen = (ord($bin[$i])<<8) + ord($bin[$i+1]); $i += 2;
      if ($seglen < 2 || $i+$seglen-2 > $len) break;
      if (in_array($marker, [0xC0,0xC2])) { // SOF0/2
        $seg = substr($bin, $i, $seglen-2);
        $h = (ord($seg[1])<<8)+ord($seg[2]); $w = (ord($seg[3])<<8)+ord($seg[4]);
        return [$w,$h];
      }
      $i += $seglen-2;
    }
    return [600,400];
  }
  public static function flyerRich(string $title, string $desc, int $price, array $imageFsPaths): string {
    $objs = [];
    // Build content stream (text + positions reserved for images draw commands)
    $y = 790;
    $txt = "BT /F1 28 Tf 60 $y Td(".self::esc($title).") Tj T* ";
    $y -= 28;
    $txt .= "/F1 12 Tf ";
    $desc = preg_replace('/\s+/', ' ', trim($desc));
    $line = ""; $words = explode(' ', $desc);
    foreach ($words as $wd) {
      if (strlen($line.$wd) > 90) { $txt .= "60 $y Td(".self::esc($line).") Tj T* "; $y -= 16; $line = $wd.' '; }
      else { $line .= $wd.' '; }
    }
    if (strlen(trim($line))>0) { $txt .= "60 $y Td(".self::esc(trim($line)).") Tj T* "; $y -= 16; }
    $txt .= "60 $y Td(Prix: ".$price." EUR) Tj T* ET\n";
    $y -= 24;

    // Prepare image objects (max 3 JPEGs)
    $imgObjs = []; $imgSpecs = []; $slot=0;
    foreach ($imageFsPaths as $p) {
      if ($slot>=3) break;
      if (!is_file($p)) continue;
      $bin = file_get_contents($p);
      if (substr($bin,0,2)!="\xFF\xD8") continue; // only JPEG
      list($w,$h) = self::jpegSize($bin);
      $name = "/Im".($slot+1);
      $stream = "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($bin)." >>\nstream\n".$bin."\nendstream";
      $imgObjs[] = $stream;
      $imgSpecs[] = [$name,$w,$h]; // will map to object numbers later
      $slot++;
    }

    // Draw images as thumbnails
    $x = 60; $thumbW = 160; $thumbH = 120; $gap = 12;
    $imgDraw = "";
    foreach ($imgSpecs as $spec) {
      list($name,$w,$h) = $spec;
      $scale = min($thumbW/$w, $thumbH/$h);
      $dw = (int)($w*$scale); $dh = (int)($h*$scale);
      $imgDraw .= "q $dw 0 0 $dh $x $y cm $name Do Q\n";
      $x += $dw + $gap;
    }
    $contentStream = "<< /Length ".strlen($txt.$imgDraw)." >>\nstream\n".$txt.$imgDraw."endstream";
    // Build objects in order: Content, Font, Images..., Page (with resources), Pages, Catalog
    $objs[] = $contentStream; $contentObj = count($objs);
    $objs[] = "<< /Type /Font /Subtype /Type1 /Name /F1 /BaseFont /Helvetica >>"; $fontObj = count($objs);
    $imgNameToObj = [];
    foreach ($imgObjs as $i=>$stream) { $objs[] = $stream; $imgNameToObj["/Im".($i+1)] = count($objs); }
    // Build Page with resources
    $xobjectParts = [];
    foreach ($imgNameToObj as $n=>$num) { $xobjectParts[] = $n." ".$num." 0 R"; }
    $xobjDict = $xobjectParts ? ("/XObject << ".implode(" ", $xobjectParts)." >>") : "";
    $page = "<< /Type /Page /Parent {PAGES} 0 R /MediaBox [0 0 595 842] /Contents ".$contentObj." 0 R /Resources << /Font << /F1 ".$fontObj." 0 R >> ".$xobjDict." >> >>";
    $objs[] = $page; $pageObj = count($objs);
    $pages = "<< /Type /Pages /Kids [".$pageObj." 0 R] /Count 1 >>"; $objs[] = $pages; $pagesObj = count($objs);
    $catalog = "<< /Type /Catalog /Pages ".$pagesObj." 0 R >>"; $objs[] = $catalog; $catalogObj = count($objs);

    // Fix the /Parent placeholder in Page object (replace {PAGES})
    $objs[$pageObj-1] = str_replace("{PAGES}", (string)$pagesObj, $objs[$pageObj-1]);

    // Assemble PDF
    $pdf="%PDF-1.4\n"; $ofs=[0];
    for ($i=0;$i<count($objs);$i++){ $ofs[] = strlen($pdf); $pdf.= ($i+1)." 0 obj\n".$objs[$i]."\nendobj\n"; }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objs)+1)."\n0000000000 65535 f \n";
    for ($i=1;$i<=count($objs);$i++){ $pdf .= sprintf("%010d 00000 n \n", $ofs[$i]); }
    $pdf .= "trailer << /Size ".(count($objs)+1)." /Root ".$catalogObj." 0 R >>\nstartxref\n".$xref."\n%%EOF";
    return $pdf;
  }
}