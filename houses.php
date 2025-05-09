<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "house_rental_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
	die("Kết nối thất bại: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$house_no = $_POST['house_no'];
	$category_id = $_POST['category_id'];
	$description = $_POST['description'];
	$price = $_POST['price'];
	$image = "";

	// Kiểm tra và tạo thư mục uploads nếu chưa tồn tại
	$target_dir = "uploads/";
	if (!is_dir($target_dir)) {
		mkdir($target_dir, 0777, true);
	}

	if (!empty($_FILES['image']['name'])) {
		$imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
		$allowed_types = ['jpg', 'jpeg', 'png'];

		if (in_array($imageFileType, $allowed_types)) {
			$image = $target_dir . time() . "_" . basename($_FILES['image']['name']);
			move_uploaded_file($_FILES['image']['tmp_name'], $image);
		} else {
			die("Chỉ chấp nhận các tệp JPG, JPEG, PNG.");
		}
	}

	$stmt = $conn->prepare("INSERT INTO houses (house_no, category_id, description, price, image) VALUES (?, ?, ?, ?, ?)");
	$stmt->bind_param("sisds", $house_no, $category_id, $description, $price, $image);
	if ($stmt->execute()) {
		echo "Lưu thành công!";
	} else {
		echo "Lỗi khi lưu: " . $stmt->error;
	}
	$stmt->close();

	header("Location: index.php"); // Chuyển hướng sau khi lưu thành công
	exit();
}
?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="row">
			<!-- FORM Panel -->
			<div class="col-md-4">
				<form action="" id="manage-house" enctype="multipart/form-data">
					<div class="card">
						<div class="card-header">
							House Form
						</div>
						<div class="card-body">
							<div class="form-group" id="msg"></div>
							<input type="hidden" name="id">
							<div class="form-group">
								<label class="control-label">House No</label>
								<input type="text" class="form-control" name="house_no" required="">
							</div>
							<div class="form-group">
								<label class="control-label">Category</label>
								<select name="category_id" id="" class="custom-select" required>
									<?php
									$categories = $conn->query("SELECT * FROM categories order by name asc");
									if ($categories->num_rows > 0):
										while ($row = $categories->fetch_assoc()) :
									?>
											<option value="<?php echo $row['id'] ?>"><?php echo $row['name'] ?></option>
										<?php endwhile; ?>
									<?php else: ?>
										<option selected="" value="" disabled="">Please check the category list.</option>
									<?php endif; ?>
								</select>
							</div>
							<div class="form-group">
								<label for="" class="control-label">Description</label>
								<textarea name="description" id="" cols="30" rows="4" class="form-control" required></textarea>
							</div>
							<div class="form-group">
								<label class="control-label">Price</label>
								<input type="number" class="form-control text-right" name="price" step="any" required="">
							</div>
							<div class="form-group">
								<label>House Image</label>
								<input type="file" class="form-control" name="image" required>
								<img id="preview-img" src="" alt="Preview" width="240" style="display: none; margin-top: 10px;">
							</div>
						</div>
						<div class="card-footer">
							<div class="row">
								<div class="col-md-12">
									<button class="btn btn-sm btn-primary col-sm-3 offset-md-3"> Save</button>
									<button class="btn btn-sm btn-default col-sm-3" type="reset"> Cancel</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<!-- FORM Panel -->

			<!-- Table Panel -->
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">
						<b>House List</b>
					</div>
					<div class="card-body">
						<table class="table table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">#</th>
									<th class="text-center">House</th>
									<th class="text-center">Image</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 1;
								$house = $conn->query("SELECT h.*,c.name as cname FROM houses h inner join categories c on c.id = h.category_id order by id asc");
								while ($row = $house->fetch_assoc()):
								?>
									<tr>
										<td class="text-center"><?php echo $i++ ?></td>
										<td class="">
											<p>House #: <b><?php echo $row['house_no'] ?></b></p>
											<p><small>House Type: <b><?php echo $row['cname'] ?></b></small></p>
											<p><small>Description: <b><?php echo $row['description'] ?></b></small></p>
											<p><small>Price: <b><?php echo number_format($row['price'], 2) ?></b></small></p>
										</td>
										<td><img src="<?php echo $row['image']; ?>" alt="House Image" width="100"></td>
										<td class="text-center">
											<button class="btn btn-sm btn-primary edit_house" type="button" data-id="<?php echo $row['id'] ?>" data-house_no="<?php echo $row['house_no'] ?>" data-description="<?php echo $row['description'] ?>" data-category_id="<?php echo $row['category_id'] ?>" data-price="<?php echo $row['price'] ?>">Edit</button>
											<button class="btn btn-sm btn-danger delete_house" type="button" data-id="<?php echo $row['id'] ?>">Delete</button>
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
	td {
		vertical-align: middle !important;
	}

	td p {
		margin: unset;
		padding: unset;
		line-height: 1em;
	}
</style>
<script>
	$('#manage-house').on('reset', function(e) {
		$('#msg').html('')
	})
	$('input[name="image"]').change(function(event) {
		let reader = new FileReader();
		reader.onload = function() {
			$('#preview-img').attr('src', reader.result).show();
		}
		reader.readAsDataURL(event.target.files[0]);
	});
	$('#manage-house').submit(function(e) {
		e.preventDefault()
		start_load()
		$('#msg').html('')
		$.ajax({
			url: 'ajax.php?action=save_house',
			data: new FormData($(this)[0]),
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Data successfully saved", 'success')
					setTimeout(function() {
						location.reload()
					}, 1500)

				} else if (resp == 2) {
					$('#msg').html('<div class="alert alert-danger">House number already exist.</div>')
					end_load()
				}
			}
		})
	})
	$('.edit_house').click(function() {
		start_load()
		var cat = $('#manage-house')
		cat.get(0).reset()
		cat.find("[name='id']").val($(this).attr('data-id'))
		cat.find("[name='house_no']").val($(this).attr('data-house_no'))
		cat.find("[name='description']").val($(this).attr('data-description'))
		cat.find("[name='price']").val($(this).attr('data-price'))
		cat.find("[name='category_id']").val($(this).attr('data-category_id'))
		cat.find("[name='image']").val($(this).attr('data-image'))
		end_load()
	})
	$('.delete_house').click(function() {
		_conf("Are you sure to delete this house?", "delete_house", [$(this).attr('data-id')])
	})

	function delete_house($id) {
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_house',
			method: 'POST',
			data: {
				id: $id
			},
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Data successfully deleted", 'success')
					setTimeout(function() {
						location.reload()
					}, 1500)

				}
			}
		})
	}
	$('table').dataTable()
</script>