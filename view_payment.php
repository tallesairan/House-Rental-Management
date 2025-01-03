<?php include 'db_connect.php' ?>

<?php 
$stmt = $conn->prepare("SELECT t.*, concat(t.lastname, ', ', t.firstname, ' ', t.middlename) as name, h.house_no, h.price
                       FROM tenants t 
                       INNER JOIN houses h ON h.id = t.house_id 
                       WHERE t.id = :id");
$stmt->execute(['id' => $_GET['id']]);
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);
foreach($tenant as $k => $v){
    if(!is_numeric($k)){
        $$k = $v;
    }
}
$months = abs(strtotime(date('Y-m-d')." 23:59:59") - strtotime($date_in." 23:59:59"));
$months = floor(($months) / (30*60*60*24));
$payable = $price * $months;
$stmtPaid = $conn->prepare("SELECT SUM(amount) as paid FROM payments WHERE tenant_id = :id");
$stmtPaid->execute(['id' => $_GET['id']]);
$paid = $stmtPaid->fetch(PDO::FETCH_ASSOC)['paid'] ?? 0;
$stmtLastPayment = $conn->prepare("SELECT * FROM payments WHERE tenant_id = :id ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
$stmtLastPayment->execute(['id' => $_GET['id']]);
if($stmtLastPayment->rowCount() > 0){
    $lp = $stmtLastPayment->fetch(PDO::FETCH_ASSOC)['date_created'];
    $last_payment = date("M d, Y", strtotime($lp));
} else {
    $last_payment = 'N/A';
}
$outstanding = $payable - $paid;

?>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="row">
			<div class="col-md-4">
				<div id="details">
					<large><b>Details</b></large>
					<hr>
					<p>Tenant: <b><?php echo ucwords($name) ?></b></p>
					<p>Monthly Rental Rate: <b><?php echo number_format($price,2) ?></b></p>
					<p>Outstanding Balance: <b><?php echo number_format($outstanding,2) ?></b></p>
					<p>Total Paid: <b><?php echo number_format($paid,2) ?></b></p>
					<p>Rent Started: <b><?php echo date("M d, Y",strtotime($date_in)) ?></b></p>
					<p>Payable Months: <b><?php echo $months ?></b></p>
				</div>
			</div>
			<div class="col-md-8">
				<large><b>Payment List</b></large>
					<hr>
				<table class="table table-condensed table-striped">
					<thead>
						<tr>
							<th>Date</th>
							<th>Invoice</th>
							<th>Amount</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$stmtPayments = $conn->prepare("SELECT * FROM payments WHERE tenant_id = :id");
						$stmtPayments->execute(['id' => $id]);
						if($stmtPayments->rowCount() > 0):
						    while($row = $stmtPayments->fetch(PDO::FETCH_ASSOC)):
						?>
					<tr>
						<td><?php echo date("M d, Y",strtotime($row['date_created'])) ?></td>
						<td><?php echo $row['invoice'] ?></td>
						<td class='text-right'><?php echo number_format($row['amount'],2) ?></td>
					</tr>
					<?php endwhile; ?>
					<?php else: ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<style>
	#details p {
		margin: unset;
		padding: unset;
		line-height: 1.3em;
	}
	td, th{
		padding: 3px !important;
	}
</style>