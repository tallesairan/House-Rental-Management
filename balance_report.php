<?php include 'db_connect.php' ?>
<style>
	.on-print{
		display: none;
	}
</style>
<noscript>
	<style>
		.text-center{
			text-align:center;
		}
		.text-right{
			text-align:right;
		}
		table{
			width: 100%;
			border-collapse: collapse
		}
		tr,td,th{
			border:1px solid black;
		}
	</style>
</noscript>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-body">
				<div class="col-md-12">
					<hr>
						<div class="row">
							<div class="col-md-12 mb-2">
							<button class="btn btn-sm btn-block btn-success col-md-2 ml-1 float-right" type="button" id="print"><i class="fa fa-print"></i> Print</button>
							</div>
						</div>
					<div id="report">
						<div class="on-print">
							 <p><center>Rental Balances Report</center></p>
							 <p><center>As of <b><?php echo date('F ,Y') ?></b></center></p>
						</div>
						<div class="row">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Tenant</th>
										<th>House #</th>
										<th>Monthly Rate</th>
										<th>Payable Months</th>
										<th>Payable Amount</th>
										<th>Paid</th>
										<th>Outstanding Balance</th>
										<th>Last Payment</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$i = 1;
									// $tamount = 0;
									$stmt = $pdo->prepare("SELECT t.*, CONCAT(t.lastname,', ',t.firstname,' ',t.middlename) AS name, h.house_no, h.price
									    FROM tenants t 
									    INNER JOIN houses h ON h.id = t.house_id 
									    WHERE t.status = 1 
									    ORDER BY h.house_no DESC");
									$stmt->execute();
									while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
										$months = abs(strtotime(date('Y-m-d')." 23:59:59") - strtotime($row['date_in']." 23:59:59"));
										$months = floor(($months) / (30*60*60*24));
										$payable = $row['price'] * $months;
										$stmt_paid = $pdo->prepare("SELECT SUM(amount) AS paid FROM payments WHERE tenant_id = :tid");
										$stmt_paid->execute([':tid' => $row['id']]);
										$p_res = $stmt_paid->fetch(PDO::FETCH_ASSOC);
										$paid = $p_res ? $p_res['paid'] : 0;
										$stmt_last = $pdo->prepare("SELECT date_created FROM payments WHERE tenant_id = :tid ORDER BY UNIX_TIMESTAMP(date_created) DESC LIMIT 1");
										$stmt_last->execute([':tid' => $row['id']]);
										$l_res = $stmt_last->fetch(PDO::FETCH_ASSOC);
										$last_payment = $l_res ? date('M d, Y', strtotime($l_res['date_created'])) : 'N/A';
										$outstanding = $payable - $paid;
									?>
									<tr>
										<td><?php echo $i++ ?></td>
										<td><?php echo ucwords($row['name']) ?></td>
										<td><?php echo $row['house_no'] ?></td>
										<td class="text-right"><?php echo number_format($row['price'],2) ?></td>
										<td class="text-right"><?php echo $months.' mo/s' ?></td>
										<td class="text-right"><?php echo number_format($payable,2) ?></td>
										<td class="text-right"><?php echo number_format($paid,2) ?></td>
										<td class="text-right"><?php echo number_format($outstanding,2) ?></td>
										<td><?php echo date('M d,Y',strtotime($last_payment)) ?></td>
									</tr>
								<?php endwhile; ?>
								<?php else: ?>
									<tr>
										<th colspan="9"><center>No Data.</center></th>
									</tr>
								<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$('#print').click(function(){
		var _style = $('noscript').clone()
		var _content = $('#report').clone()
		var nw = window.open("","_blank","width=800,height=700");
		nw.document.write(_style.html())
		nw.document.write(_content.html())
		nw.document.close()
		nw.print()
		setTimeout(function(){
		nw.close()
		},500)
	})
	$('#filter-report').submit(function(e){
		e.preventDefault()
		location.href = 'index.php?page=payment_report&'+$(this).serialize()
	})
</script>