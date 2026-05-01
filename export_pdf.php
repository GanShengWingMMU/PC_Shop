<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once 'config.php';
require_once 'fpdf.php'; // 引入刚才下载的 FPDF 库

// 1. 安全校验：必须登录且提供了 build ID
if (!isset($_SESSION['customer_id']) || !isset($_GET['id'])) {
    die("Access Denied.");
}

$customer_id = $_SESSION['customer_id'];
$build_id = intval($_GET['id']);

// 2. 获取用户和蓝图基本信息
$build_sql = "SELECT sb.*, c.username 
              FROM saved_builds sb 
              JOIN customers c ON sb.customer_id = c.customer_id 
              WHERE sb.pc_build = ? AND sb.customer_id = ?";
$stmt = $conn->prepare($build_sql);
$stmt->bind_param("ii", $build_id, $customer_id);
$stmt->execute();
$build_res = $stmt->get_result();

if ($build_res->num_rows === 0) {
    die("Blueprint not found.");
}
$build_data = $build_res->fetch_assoc();

// 3. 开始构建 PDF
$pdf = new FPDF();
$pdf->AddPage();

// --- 头部设计 ---
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor(15, 23, 42); // 深蓝黑色
$pdf->Cell(100, 10, 'GridCitY PC', 0, 0, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(100, 116, 139); // 灰色
$pdf->Cell(90, 10, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 242, 254); // 赛博蓝主题色
$pdf->Cell(190, 8, 'ULTIMATE BLUEPRINT', 0, 1, 'L');
$pdf->Ln(5);

// 画一条分割线
$pdf->SetDrawColor(203, 213, 225);
$pdf->Line(10, 35, 200, 35);
$pdf->Ln(5);

// --- 客户与订单信息 ---
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(30, 8, 'Customer:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(65, 8, $build_data['username'], 0, 0);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(35, 8, 'Blueprint Name:', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 8, $build_data['build_name'], 0, 1);
$pdf->Ln(5);

// --- 表格头部 ---
$pdf->SetFillColor(241, 245, 249); // 浅灰底色
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(60, 10, ' Component Category', 1, 0, 'L', true);
$pdf->Cell(130, 10, ' Selected Hardware', 1, 1, 'L', true);

// --- 抓取并循环打印零件列表 ---
$items_sql = "SELECT c.category_name, p.product_name 
              FROM build_items bi 
              JOIN products p ON bi.product_id = p.product_id 
              JOIN categories c ON p.category_id = c.category_id 
              WHERE bi.pc_build = ?";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->bind_param("i", $build_id);
$stmt_items->execute();
$items_res = $stmt_items->get_result();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(51, 65, 85);

while ($item = $items_res->fetch_assoc()) {
    // 处理过长的产品名称，MultiCell 可能会破坏表格对齐，这里我们截断超长字符串保证排版
    $cat_name = substr($item['category_name'], 0, 30);
    $prod_name = substr($item['product_name'], 0, 75);
    
    $pdf->Cell(60, 10, ' ' . $cat_name, 1, 0, 'L');
    $pdf->Cell(130, 10, ' ' . $prod_name, 1, 1, 'L');
}
$pdf->Ln(5);

// --- 尾部总计金额 ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(190, 8, 'ESTIMATED TOTAL', 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(0, 180, 216);
$pdf->Cell(190, 10, 'RM ' . number_format($build_data['total_price'], 2), 0, 1, 'R');

// 画底部分割线
$pdf->Line(10, $pdf->GetY() + 5, 200, $pdf->GetY() + 5);

// 4. 强制浏览器下载文件
// 清除任何可能的输出缓冲，防止 PDF 损坏
if (ob_get_length()) ob_end_clean(); 
// 文件名清理，防止特殊字符报错
$clean_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $build_data['build_name']);
$pdf->Output('D', 'GridCitY_Blueprint_' . $clean_filename . '.pdf');
exit();
?>