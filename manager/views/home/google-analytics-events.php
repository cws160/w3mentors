<?php
if (isset($error)) {
    echo "<p class='text-center w-100'>" . $errorMsg . "</p>";
    exit;
}
$chartEventData = [];
array_push($chartEventData, ['Task', 'Event Data']);
foreach ($statsInfo as $key => $row) {
    $series = [];
    if ($key == 'page_view') {
        continue;
    }
    $series[] = ucwords(str_replace('_', ' ', $key));
    $series[] = FatUtility::int($row);
    $chartEventData[] = $series;
}
?>
<script>
    chartEventData = <?php echo json_encode($chartEventData); ?>
</script>