<?php
if (isset($error)) {
    echo "<p class='text-center w-100'>" . $errorMsg . "</p>";
    die;
}
$chartTrafficData = [];
array_push($chartTrafficData, ['Task', 'Traffic Data']);
foreach ($statsInfo as $key => $row) {
    $series = [];
    if (!in_array(str_replace('_', ' ', $key), ['Direct', 'Referral', 'Unassigned'])) {
        continue;
    }
    $series[] = ucwords(str_replace('_', ' ', $key));
    $series[] = FatUtility::int($row);
    $chartTrafficData[] = $series;
}
?>
<script>
    chartTrafficData = <?php echo json_encode($chartTrafficData); ?>
</script>
