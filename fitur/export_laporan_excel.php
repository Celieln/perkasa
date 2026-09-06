<?php

include '../config.php';

if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}
if(!is_admin()){
    header("Location: ../dashboard.php");
    exit;
}

set_time_limit(0);
ini_set("memory_limit", "1024M");

$where = "";
$program = isset($_GET["program"]) ? mysqli_real_escape_string($conn, $_GET["program"]) : "";
$status_filter = isset($_GET["status"]) ? $_GET["status"] : "";

if($program != "") {
    $where .= " AND tipe_program=" . $conn->real_escape_string($program) . "";
}
if($status_filter == "claim") {
    $where .= " AND status_klaim IN ('proses_claim','sudah_claim')";
} elseif($status_filter == "cair") {
    $where .= " AND status_klaim IN ('proses_cair','sudah_cair')";
}

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM peserta WHERE 1=1 $where"))["c"];

$rekomLabels = ["belum" => "Belum Mengeluarkan Rekom", "proses" => "Memproses Rekom", "selesai" => "Rekom Telah Selesai"];
$klaimLabels = ["belum" => "Belum", "proses_cair" => "Proses Cair", "proses_claim" => "Proses Claim", "sudah_cair" => "Sudah Cair", "sudah_claim" => "Sudah Claim"];

$headers = ["No","Tracking Code","NIK","No KK","Nama Lengkap","Tempat Lahir","Tanggal Lahir","Jenis Kelamin",
    "Alamat","RT","RW","Kelurahan","Kecamatan","Kota","Provinsi","Agama","Denominasi","Profesi","Pekerjaan","Program",
    "Tgl Meninggal","Ahli Waris","No Telp Ahli Waris","Status Ahli Waris","Pekerjaan Ahli Waris","Alamat Ahli Waris",
    "RT Ahli Waris","RW Ahli Waris","Kelurahan Ahli Waris","Kecamatan Ahli Waris","Kota Ahli Waris","Provinsi Ahli Waris",
    "Status Rekom","Status Klaim"];

$tmpdir = sys_get_temp_dir() . "/prk_export_" . bin2hex(random_bytes(8));
mkdir($tmpdir, 0777, true);
mkdir($tmpdir . "/xl/worksheets", 0777, true);
mkdir($tmpdir . "/xl/_rels", 0777, true);
mkdir($tmpdir . "/_rels", 0777, true);

function xlsEscape($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function xlsCell($col, $row, $val, $type = "inlineStr"){
    $ref = $col . $row;
    if($type === "inlineStr"){
        return "<c r=\"" . $ref . "\" t=\"inlineStr\"><is><t xml:space=\"preserve\">" . xlsEscape($val) . "</t></is></c>";
    }
    return "<c r=\"" . $ref . "\" t=\"n\"><v>" . (float)$val . "</v></c>";
}

function xlsColLetter($i){
    if($i < 26) return chr(65 + $i);
    return "A" . chr(65 + $i - 26);
}

// Sheet XML
$sheet = new XMLWriter();
$sheet->openMemory();
$sheet->setIndent(true);
$sheet->startDocument("1.0", "UTF-8");

$sheet->startElement("worksheet");
$sheet->writeAttribute("xmlns", "http://schemas.openxmlformats.org/spreadsheetml/2006/main");
$sheet->writeAttribute("xmlns:r", "http://schemas.openxmlformats.org/officeDocument/2006/relationships");

// Title row (merged)
$sheet->startElement("mergeCells");
$sheet->writeAttribute("count", "1");
$sheet->startElement("mergeCell");
$sheet->writeAttribute("ref", "A1:AH1");
$sheet->endElement();
$sheet->endElement();

$sheet->startElement("cols");
$widths = [5,18,18,18,30,18,14,14,35,6,6,18,18,18,18,14,18,20,20,10,14,25,18,18,20,30,6,6,18,18,18,18,25,18];
foreach($widths as $i => $w){
    $sheet->startElement("col");
    $sheet->writeAttribute("min", $i + 1);
    $sheet->writeAttribute("max", $i + 1);
    $sheet->writeAttribute("width", $w);
    $sheet->writeAttribute("customWidth", "1");
    $sheet->endElement();
}
$sheet->endElement();

$sheet->startElement("sheetData");

// Title row
$sheet->startElement("row");
$sheet->writeAttribute("r", "1");
$sheet->startElement("c");
$sheet->writeAttribute("r", "A1");
$sheet->writeAttribute("t", "inlineStr");
$sheet->writeAttribute("s", "2");
$sheet->startElement("is");
$sheet->startElement("t");
$sheet->writeAttribute("xml:space", "preserve");
$sheet->text("LAPORAN DATA PESERTA PERKASA");
$sheet->endElement();
$sheet->endElement();
$sheet->endElement();
$sheet->endElement();

// Header row
$totalCols = count($headers);
$sheet->startElement("row");
$sheet->writeAttribute("r", "2");
foreach($headers as $i => $h){
    $sheet->startElement("c");
    $sheet->writeAttribute("r", xlsColLetter($i) . "2");
    $sheet->writeAttribute("t", "inlineStr");
    $sheet->writeAttribute("s", "1");
    $sheet->startElement("is");
    $sheet->startElement("t");
    $sheet->writeAttribute("xml:space", "preserve");
    $sheet->text($h);
    $sheet->endElement();
    $sheet->endElement();
    $sheet->endElement();
}
$sheet->endElement();

// Data rows
$q = mysqli_query($conn, "SELECT * FROM peserta WHERE 1=1 $where ORDER BY id DESC");
$no = 1;
$rowNum = 3;
while($d = mysqli_fetch_assoc($q)){
    $rekom = $rekomLabels[$d["status_rekom"] ?? "belum"] ?? $d["status_rekom"];
    $klaim = $klaimLabels[$d["status_klaim"] ?? "belum"] ?? $d["status_klaim"];

    $vals = [
        $no, $d["tracking_code"], $d["nik"], $d["no_kk"], $d["nama_lengkap"],
        $d["tempat_lahir"], $d["tanggal_lahir"], $d["jenis_kelamin"],
        $d["alamat"], $d["rt"], $d["rw"], $d["kelurahan"], $d["kecamatan"],
        $d["kota"], $d["provinsi"], $d["agama"], $d["denominasi"],
        $d["profesi"], $d["pekerjaan"], $d["tipe_program"],
        $d["tanggal_meninggal"], $d["nama_ahli_waris"], $d["no_telp_ahli_waris"],
        $d["status_ahli_waris"], $d["pekerjaan_ahli_waris"], $d["alamat_ahli_waris"],
        $d["rt_ahli_waris"], $d["rw_ahli_waris"], $d["kelurahan_ahli_waris"],
        $d["kecamatan_ahli_waris"], $d["kota_ahli_waris"], $d["provinsi_ahli_waris"],
        $rekom, $klaim,
    ];

    $styleId = $no % 2 == 0 ? 3 : 2; // 2=normal, 3=zebra
    $firstCol = xlsColLetter(0) . $rowNum;

    $sheet->startElement("row");
    $sheet->writeAttribute("r", $rowNum);

    // No column (centered)
    $sheet->startElement("c");
    $sheet->writeAttribute("r", $firstCol);
    $sheet->writeAttribute("t", "inlineStr");
    $sheet->writeAttribute("s", "4");
    $sheet->startElement("is");
    $sheet->startElement("t");
    $sheet->writeAttribute("xml:space", "preserve");
    $sheet->text($no);
    $sheet->endElement();
    $sheet->endElement();
    $sheet->endElement();

    // Other columns
    for($i = 1; $i < $totalCols; $i++){
        $v = $vals[$i];
        $sheet->startElement("c");
        $sheet->writeAttribute("r", xlsColLetter($i) . $rowNum);
        $sheet->writeAttribute("t", "inlineStr");
        $sheet->writeAttribute("s", $styleId);
        $sheet->startElement("is");
        $sheet->startElement("t");
        $sheet->writeAttribute("xml:space", "preserve");
        $sheet->text($v);
        $sheet->endElement();
        $sheet->endElement();
        $sheet->endElement();
    }

    $sheet->endElement();

    $no++;
    $rowNum++;

    if($no % 5000 == 0){
        $sheet->flush();
    }
}

$sheet->endElement(); // sheetData
$sheet->endElement(); // worksheet
$sheet->endDocument();
$sheetXml = $sheet->outputMemory();
$sheet->flush();
file_put_contents($tmpdir . "/xl/worksheets/sheet1.xml", $sheetXml);
unset($sheetXml);

// Styles
$styles = new XMLWriter();
$styles->openMemory();
$styles->setIndent(true);
$styles->startDocument("1.0", "UTF-8");
$styles->startElement("styleSheet");
$styles->writeAttribute("xmlns", "http://schemas.openxmlformats.org/spreadsheetml/2006/main");

$styles->startElement("fonts");
$styles->writeAttribute("count", "3");
// font 0: default
$styles->startElement("font");
$styles->startElement("sz"); $styles->writeAttribute("val", "11"); $styles->endElement();
$styles->startElement("name"); $styles->writeAttribute("val", "Calibri"); $styles->endElement();
$styles->endElement();
// font 1: bold header
$styles->startElement("font");
$styles->startElement("b"); $styles->endElement();
$styles->startElement("sz"); $styles->writeAttribute("val", "11"); $styles->endElement();
$styles->startElement("color"); $styles->writeAttribute("rgb", "FFFFFFFF"); $styles->endElement();
$styles->startElement("name"); $styles->writeAttribute("val", "Calibri"); $styles->endElement();
$styles->endElement();
// font 2: title
$styles->startElement("font");
$styles->startElement("b"); $styles->endElement();
$styles->startElement("sz"); $styles->writeAttribute("val", "16"); $styles->endElement();
$styles->startElement("name"); $styles->writeAttribute("val", "Calibri"); $styles->endElement();
$styles->endElement();
$styles->endElement();

$styles->startElement("fills");
$styles->writeAttribute("count", "4");
$styles->startElement("fill");
$styles->startElement("patternFill");
$styles->writeAttribute("patternType", "none");
$styles->endElement();
$styles->endElement();
$styles->startElement("fill");
$styles->startElement("patternFill");
$styles->writeAttribute("patternType", "gray125");
$styles->endElement();
$styles->endElement();
$styles->startElement("fill");
$styles->startElement("patternFill");
$styles->writeAttribute("patternType", "solid");
$styles->startElement("fgColor"); $styles->writeAttribute("rgb", "FF0D6EFD"); $styles->endElement();
$styles->startElement("bgColor"); $styles->writeAttribute("indexed", "64"); $styles->endElement();
$styles->endElement();
$styles->endElement();
$styles->startElement("fill");
$styles->startElement("patternFill");
$styles->writeAttribute("patternType", "solid");
$styles->startElement("fgColor"); $styles->writeAttribute("rgb", "FFF2F7FF"); $styles->endElement();
$styles->startElement("bgColor"); $styles->writeAttribute("indexed", "64"); $styles->endElement();
$styles->endElement();
$styles->endElement();
$styles->endElement();

$styles->startElement("borders");
$styles->writeAttribute("count", "2");
$styles->startElement("border");
$styles->startElement("left"); $styles->endElement();
$styles->startElement("right"); $styles->endElement();
$styles->startElement("top"); $styles->endElement();
$styles->startElement("bottom"); $styles->endElement();
$styles->startElement("diagonal"); $styles->endElement();
$styles->endElement();
$styles->startElement("border");
$sides = ["left","right","top","bottom","diagonal"];
foreach($sides as $side){
    $styles->startElement($side);
    $styles->writeAttribute("style", "thin");
    $styles->startElement("color"); $styles->writeAttribute("rgb", "FF999999"); $styles->endElement();
    $styles->endElement();
}
$styles->endElement();
$styles->endElement();

$styles->startElement("cellStyleXfs");
$styles->writeAttribute("count", "1");
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "0");
$styles->writeAttribute("fillId", "0");
$styles->writeAttribute("borderId", "0");
$styles->endElement();
$styles->endElement();

$styles->startElement("cellXfs");
$styles->writeAttribute("count", "5");
// xf0: default
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "0");
$styles->writeAttribute("fillId", "0");
$styles->writeAttribute("borderId", "0");
$styles->writeAttribute("xfId", "0");
$styles->endElement();
// xf1: header
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "1");
$styles->writeAttribute("fillId", "2");
$styles->writeAttribute("borderId", "1");
$styles->writeAttribute("xfId", "0");
$styles->writeAttribute("applyFont", "1");
$styles->writeAttribute("applyFill", "1");
$styles->writeAttribute("applyBorder", "1");
$styles->writeAttribute("applyAlignment", "1");
$styles->startElement("alignment");
$styles->writeAttribute("horizontal", "center");
$styles->writeAttribute("vertical", "center");
$styles->writeAttribute("wrapText", "1");
$styles->endElement();
$styles->endElement();
// xf2: data with border
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "0");
$styles->writeAttribute("fillId", "0");
$styles->writeAttribute("borderId", "1");
$styles->writeAttribute("xfId", "0");
$styles->writeAttribute("applyBorder", "1");
$styles->writeAttribute("applyAlignment", "1");
$styles->startElement("alignment");
$styles->writeAttribute("vertical", "top");
$styles->endElement();
$styles->endElement();
// xf3: data + zebra
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "0");
$styles->writeAttribute("fillId", "3");
$styles->writeAttribute("borderId", "1");
$styles->writeAttribute("xfId", "0");
$styles->writeAttribute("applyFill", "1");
$styles->writeAttribute("applyBorder", "1");
$styles->writeAttribute("applyAlignment", "1");
$styles->startElement("alignment");
$styles->writeAttribute("vertical", "top");
$styles->endElement();
$styles->endElement();
// xf4: center + border (for No)
$styles->startElement("xf");
$styles->writeAttribute("numFmtId", "0");
$styles->writeAttribute("fontId", "0");
$styles->writeAttribute("fillId", "0");
$styles->writeAttribute("borderId", "1");
$styles->writeAttribute("xfId", "0");
$styles->writeAttribute("applyBorder", "1");
$styles->writeAttribute("applyAlignment", "1");
$styles->startElement("alignment");
$styles->writeAttribute("horizontal", "center");
$styles->writeAttribute("vertical", "top");
$styles->endElement();
$styles->endElement();
$styles->endElement();

$styles->endElement(); // styleSheet
$styles->endDocument();
file_put_contents($tmpdir . "/xl/styles.xml", $styles->outputMemory());
$styles->flush();

// Content Types
$ct = new XMLWriter();
$ct->openMemory();
$ct->setIndent(true);
$ct->startDocument("1.0", "UTF-8");
$ct->startElement("Types");
$ct->writeAttribute("xmlns", "http://schemas.openxmlformats.org/package/2006/content-types");
$ct->startElement("Default");
$ct->writeAttribute("Extension", "rels");
$ct->writeAttribute("ContentType", "application/vnd.openxmlformats-package.relationships+xml");
$ct->endElement();
$ct->startElement("Default");
$ct->writeAttribute("Extension", "xml");
$ct->writeAttribute("ContentType", "application/xml");
$ct->endElement();
$ct->startElement("Override");
$ct->writeAttribute("PartName", "/xl/workbook.xml");
$ct->writeAttribute("ContentType", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.mainExternal+xml");
$ct->endElement();
$ct->startElement("Override");
$ct->writeAttribute("PartName", "/xl/worksheets/sheet1.xml");
$ct->writeAttribute("ContentType", "application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml");
$ct->endElement();
$ct->startElement("Override");
$ct->writeAttribute("PartName", "/xl/styles.xml");
$ct->writeAttribute("ContentType", "application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml");
$ct->endElement();
$ct->endElement();
$ct->endDocument();
file_put_contents($tmpdir . "/[Content_Types].xml", $ct->outputMemory());

// Root rels
$rels = new XMLWriter();
$rels->openMemory();
$rels->setIndent(true);
$rels->startDocument("1.0", "UTF-8");
$rels->startElement("Relationships");
$rels->writeAttribute("xmlns", "http://schemas.openxmlformats.org/package/2006/relationships");
$rels->startElement("Relationship");
$rels->writeAttribute("Id", "rId1");
$rels->writeAttribute("Type", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument");
$rels->writeAttribute("Target", "xl/workbook.xml");
$rels->endElement();
$rels->endElement();
$rels->endDocument();
file_put_contents($tmpdir . "/_rels/.rels", $rels->outputMemory());

// Workbook rels
$wbRels = new XMLWriter();
$wbRels->openMemory();
$wbRels->setIndent(true);
$wbRels->startDocument("1.0", "UTF-8");
$wbRels->startElement("Relationships");
$wbRels->writeAttribute("xmlns", "http://schemas.openxmlformats.org/package/2006/relationships");
$wbRels->startElement("Relationship");
$wbRels->writeAttribute("Id", "rId1");
$wbRels->writeAttribute("Type", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet");
$wbRels->writeAttribute("Target", "worksheets/sheet1.xml");
$wbRels->endElement();
$wbRels->startElement("Relationship");
$wbRels->writeAttribute("Id", "rId2");
$wbRels->writeAttribute("Type", "http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles");
$wbRels->writeAttribute("Target", "styles.xml");
$wbRels->endElement();
$wbRels->endElement();
$wbRels->endDocument();
file_put_contents($tmpdir . "/xl/_rels/workbook.xml.rels", $wbRels->outputMemory());

// Workbook
$wb = new XMLWriter();
$wb->openMemory();
$wb->setIndent(true);
$wb->startDocument("1.0", "UTF-8");
$wb->startElement("workbook");
$wb->writeAttribute("xmlns", "http://schemas.openxmlformats.org/spreadsheetml/2006/main");
$wb->writeAttribute("xmlns:r", "http://schemas.openxmlformats.org/officeDocument/2006/relationships");
$wb->startElement("sheets");
$wb->startElement("sheet");
$wb->writeAttribute("name", "Laporan PERKASA");
$wb->writeAttribute("sheetId", "1");
$wb->writeAttribute("r:id", "rId1");
$wb->endElement();
$wb->endElement();
$wb->endElement();
$wb->endDocument();
file_put_contents($tmpdir . "/xl/workbook.xml", $wb->outputMemory());

// ZIP
$zip = new ZipArchive();
$zipPath = $tmpdir . "/output.xlsx";
if($zip->open($zipPath, ZipArchive::CREATE) !== true){
    die("Tidak dapat membuat file Excel");
}
$files = [
    "[Content_Types].xml",
    "_rels/.rels",
    "xl/workbook.xml",
    "xl/_rels/workbook.xml.rels",
    "xl/styles.xml",
    "xl/worksheets/sheet1.xml",
];
foreach($files as $f){
    $zip->addFile($tmpdir . "/" . $f, $f);
}
$zip->close();

$size = filesize($zipPath);

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"Laporan_PERKASA_" . date("Ymd_His") . ".xlsx\"");
header("Content-Length: " . $size);
header("Cache-Control: max-age=0");
readfile($zipPath);

// Cleanup
$dirIt = new RecursiveDirectoryIterator($tmpdir, RecursiveDirectoryIterator::SKIP_DOTS);
$filesIt = new RecursiveIteratorIterator($dirIt, RecursiveIteratorIterator::CHILD_FIRST);
foreach($filesIt as $f){
    if($f->isDir()) rmdir($f->getPathname());
    else unlink($f->getPathname());
}
rmdir($tmpdir);
exit;