<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class ExportController {
    public static function csv() {
        require_once __DIR__ . '/../helpers/auth.php';
        $db = getDB();

        $where = "WHERE deleted_at IS NULL";
        $params = array();

        if (!empty($_GET['kategori'])) {
            $where .= " AND kategori = ?";
            $params[] = $_GET['kategori'];
        }
        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['jenis_penggunaan'])) {
            $where .= " AND jenis_penggunaan = ?";
            $params[] = $_GET['jenis_penggunaan'];
        }

        $stmt = $db->prepare("SELECT * FROM spareparts $where ORDER BY created_at DESC");
        $stmt->execute($params);
        $spareparts = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=spareparts-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['ID', 'Kategori', 'Jenis Penggunaan', 'Lokasi Penyimpanan', 'Min Stok', 'Jenis', 'Type', 'Serial Number', 'Quantity', 'Tanggal', 'Merk', 'PIC', 'Department', 'Status', 'Keterangan']);

        foreach ($spareparts as $sp) {
            fputcsv($output, [
                $sp['id'],
                $sp['kategori'],
                $sp['jenis_penggunaan'],
                $sp['lokasi_penyimpanan'],
                $sp['minimum_stok'],
                $sp['jenis_sparepart'],
                $sp['type_sparepart'],
                $sp['serial_number'],
                $sp['quantity'],
                $sp['tanggal'],
                $sp['merk'],
                $sp['pic'],
                $sp['department'],
                $sp['status'],
                $sp['keterangan'],
            ]);
        }

        fclose($output);
        exit;
    }

    public static function pdf() {
        require_once __DIR__ . '/../helpers/auth.php';
        $db = getDB();

        $where = "WHERE deleted_at IS NULL";
        $params = array();

        if (!empty($_GET['kategori'])) {
            $where .= " AND kategori = ?";
            $params[] = $_GET['kategori'];
        }
        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['jenis_penggunaan'])) {
            $where .= " AND jenis_penggunaan = ?";
            $params[] = $_GET['jenis_penggunaan'];
        }

        $stmt = $db->prepare("SELECT * FROM spareparts $where ORDER BY created_at DESC");
        $stmt->execute($params);
        $spareparts = $stmt->fetchAll();

        $stats = $db->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Tersedia' THEN 1 ELSE 0 END) as tersedia,
                SUM(CASE WHEN status = 'Terpakai' THEN 1 ELSE 0 END) as terpakai,
                SUM(CASE WHEN status = 'Rusak' THEN 1 ELSE 0 END) as rusak,
                SUM(CASE WHEN status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as dalam_perbaikan
            FROM spareparts WHERE deleted_at IS NULL
        ")->fetch();

        $html = '<html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= 'body { font-family: sans-serif; font-size: 11px; }';
        $html .= 'h1 { text-align: center; color: #1e40af; margin-bottom: 5px; font-size: 18px; }';
        $html .= '.date { text-align: center; color: #666; font-size: 10px; margin-bottom: 20px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; }';
        $html .= 'th { background: #1e40af; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }';
        $html .= 'td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }';
        $html .= 'tr:nth-child(even) { background: #f9f9f9; }';
        $html .= '.stats { width: 100%; margin-bottom: 20px; }';
        $html .= '.stats td { text-align: center; padding: 8px 15px; background: #f0f0f0; }';
        $html .= '.stats .num { font-size: 16px; font-weight: bold; }';
        $html .= '.stats .label { font-size: 9px; color: #666; }';
        $html .= '.footer { text-align: center; color: #999; font-size: 9px; margin-top: 20px; }';
        $html .= '</style></head><body>';

        $html .= '<h1>Laporan Inventaris Sparepart</h1>';
        $html .= '<p class="date">Tanggal: ' . date('d F Y') . '</p>';

        $html .= '<table class="stats"><tr>';
        $html .= '<td><div class="num">' . $stats['total'] . '</div><div class="label">Total</div></td>';
        $html .= '<td><div class="num" style="color:#16a34a">' . $stats['tersedia'] . '</div><div class="label">Tersedia</div></td>';
        $html .= '<td><div class="num" style="color:#dc2626">' . $stats['terpakai'] . '</div><div class="label">Terpakai</div></td>';
        $html .= '<td><div class="num" style="color:#ca8a04">' . $stats['rusak'] . '</div><div class="label">Rusak</div></td>';
        $html .= '<td><div class="num" style="color:#2563eb">' . $stats['dalam_perbaikan'] . '</div><div class="label">Perbaikan</div></td>';
        $html .= '</tr></table>';

        $html .= '<table><thead><tr>';
        $html .= '<th>ID</th><th>Jenis</th><th>Type</th><th>Merk</th><th>SN</th>';
        $html .= '<th>Kategori</th><th>Jenis Penggunaan</th><th>Lokasi</th><th>Status</th><th>PIC</th><th>Tanggal</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($spareparts as $sp) {
            $html .= '<tr>';
            $html .= '<td>#'.htmlspecialchars($sp['id']).'</td>';
            $html .= '<td>'.htmlspecialchars($sp['jenis_sparepart']).'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['type_sparepart']) ? $sp['type_sparepart'] : '-').'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['merk']) ? $sp['merk'] : '-').'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['serial_number']) ? $sp['serial_number'] : '-').'</td>';
            $html .= '<td>'.htmlspecialchars($sp['kategori']).'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['jenis_penggunaan']) ? $sp['jenis_penggunaan'] : '-').'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['lokasi_penyimpanan']) ? $sp['lokasi_penyimpanan'] : '-').'</td>';
            $html .= '<td>'.htmlspecialchars($sp['status']).'</td>';
            $html .= '<td>'.htmlspecialchars(isset($sp['pic']) ? $sp['pic'] : '-').'</td>';
            $html .= '<td>'.date('d/m/Y', strtotime($sp['tanggal'])).'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<p class="footer">Dicetak pada '.date('d F Y H:i').' | '.htmlspecialchars(APP_NAME).'</p>';
        $html .= '</body></html>';

        require_once __DIR__ . '/../vendor/mpdf/mpdf.php';
        ob_end_clean();
        $mpdf = new mPDF('', 'A4', 0, '', 10, 10, 10, 10);
        $mpdf->WriteHTML($html);
        $mpdf->Output('laporan-sparepart-' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }
}
