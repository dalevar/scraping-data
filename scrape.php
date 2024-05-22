<?php
require 'vendor/autoload.php';

use duzun\hQuery;

$url = 'https://scholar.google.co.id/citations?user=6eDyhRgAAAAJ&hl=id';

function getCurlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    $content = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);
    return $content;
}

$htmlContent = getCurlContent($url);

try {
    $html = hQuery::fromHTML($htmlContent);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

function getTextOrNull($element)
{
    return $element ? $element->text() : null;
}


// Mendapatkan data kutipan, indeks-h, dan indeks-i10
$citations = getTextOrNull($html->find('td.gsc_rsb_std:eq(0)'));
$hIndex = getTextOrNull($html->find('td.gsc_rsb_std:eq(2)'));
$i10Index = getTextOrNull($html->find('td.gsc_rsb_std:eq(4)'));


$years = $html->find('span.gsc_g_t');
$citationCounts = $html->find('span.gsc_g_al');

$data = [];

// Periksa apakah data tahun dan kutipan ditemukan
if ($years && $citationCounts && count($years) === count($citationCounts)) {
    foreach ($years as $index => $year) {
        $data[] = [
            'year' => getTextOrNull($year),
            'citations' => (int) getTextOrNull($citationCounts[$index]),
        ];
    }
} else {
    echo 'Data tahun atau kutipan tidak ditemukan atau jumlahnya tidak sesuai.';
}

// Mengonversi data ke format yang diterima oleh Google Charts
$jsonData = json_encode($data);
var_dump($jsonData);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraping Google Scholar Data</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {
            packages: ['corechart']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Year');
            data.addColumn('number', 'Citations');

            var jsonData = <?php echo $jsonData; ?>;
            data.addRows(jsonData.map(item => [item.year, item.citations]));

            var options = {
                title: 'Citations Over Time',
                curveType: 'function',
                legend: {
                    position: 'bottom'
                }
            };

            var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

            chart.draw(data, options);
        }
    </script>
</head>

<body>
    <h1>Google Scholar Data</h1>
    <table>

        <tr>
            <th> </th>
            <th>Semua</th>
        </tr>
        <tr>
            <td>Kutipan </td>
            <td><?= htmlspecialchars($citations) ?></td>
        </tr>
        <tr>
            <td>Indeks-h</td>
            <td> <?= htmlspecialchars($hIndex) ?></td>
        </tr>
        <tr>
            <td>Indeks-i10 </td>
            <td><?= htmlspecialchars($i10Index) ?></td>
        </tr>
    </table>

    <div id="chart_div"></div>
</body>

</html>