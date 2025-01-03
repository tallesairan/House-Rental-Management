<?php include('db_connect.php');?>

<div class="container-fluid">
	
	<div class="col-lg-12">
		<div class="row mb-4 mt-4">
			<div class="col-md-12">
				
			</div>
		</div>
		<div class="row">
			<!-- FORM Panel -->

			<!-- Table Panel -->
			<div class="col-md-12">
				<div class="card">
					<div class="card-header">
						<b>List of Payments</b>
						<span class="float:right"><a class="btn btn-primary btn-block btn-sm col-sm-2 float-right" href="javascript:void(0)" id="new_payment">
					<i class="fa fa-plus"></i> New Entry
				</a></span>
					</div>
					<div class="card-body">
						<table class="table table-condensed table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">#</th>
									<th class="">Tenant</th>
									<th class="">House #</th>
									<th class="">Outstanding Balance</th>
									<th class="">Last Payment</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$i = 1;
								$stmt = $conn->prepare("SELECT t.*, CONCAT(t.lastname,', ',t.firstname,' ',t.middlename) AS name, h.house_no, h.price 
	FROM tenants t 
	INNER JOIN houses h ON h.id = t.house_id 
	WHERE t.status = 1 
	ORDER BY h.house_no DESC");
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
	$months = abs(strtotime(date('Y-m-d')." 23:59:59") - strtotime($row['date_in']." 23:59:59"));
	$months = floor(($months) / (30*60*60*24));
	$payable = $row['price'] * $months;
	$stmtPaid = $conn->prepare("SELECT SUM(amount) as paid FROM payments WHERE tenant_id = :id");
	$stmtPaid->execute([':id'=>$row['id']]);
	$p = $stmtPaid->fetch(PDO::FETCH_ASSOC);
	$paid = $p ? $p['paid'] : 0;
	$stmtLast = $conn->prepare("SELECT * FROM payments WHERE tenant_id = :id ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
	$stmtLast->execute([':id'=>$row['id']]);
	$lp = $stmtLast->fetch(PDO::FETCH_ASSOC);
	$last_payment = $lp ? date("M d, Y", strtotime($lp['date_created'])) : 'N/A';
	$outstanding = $payable - $paid;
									
								?>
								<tr>
									<td class="text-center"><?php echo $i++ ?></td>
									<td class="">
										 <p> <b><?php echo ucwords($row['name']) ?></b></p>
									</td>
									<td class="">
										 <p> <b><?php echo $row['house_no'] ?></b></p>
									</td>
									<td class="text-right">
										 <p> <b><?php echo number_format($outstanding,2) ?></b></p>
									</td>
									<td class="">
										 <p><b><?php echo  $last_payment ?></b></p>
									</td>
									<td class="text-center">
										<button class="btn btn-sm btn-outline-primary view_payment" type="button" data-id="<?php echo $row['id'] ?>" >View</button>
									</td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<!-- Table Panel -->
		</div>
	</div>	

</div>
<style>
	
	td{
		vertical-align: middle !important;
	}
	td p{
		margin: unset
	}
	img{
		max-width:100px;
		max-height: :150px;
	}
</style>
<script>
	$(document).ready(function(){
		$('table').dataTable()
	})
	
	$('#new_payment').click(function(){
		uni_modal("New payment","manage_payment.php","mid-large")
		
	})
	$('.edit_payment').click(function(){
		uni_modal("Manage payment Details","manage_payment.php?id="+$(this).attr('data-id'),"mid-large")
		
	})
	$('.view_payment').click(function(){
		uni_modal("Tenants Payments","view_payment.php?id="+$(this).attr('data-id'),"mid-large")
		
	})
	$('.delete_payment').click(function(){
		_conf("Are you sure to delete this payment?","delete_payment",[$(this).attr('data-id')])
	})
	
	function delete_payment($id){
		start_load()
		$.ajax({
			url:'ajax.php?action=delete_payment',
			method:'POST',
			data:{id:$id},
			success:function(resp){
				if(resp==1){
					alert_toast("Data successfully deleted",'success')
					setTimeout(function(){
						location.reload()
					},1500)

				}
			}
		})
	}
</script>