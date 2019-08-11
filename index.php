<?php
require __DIR__ . '/vendor/autoload.php';
use Carbon\Carbon;

error_reporting(E_ALL);

// insert new entry


// retrieve last inserted entries
putenv('GOOGLE_APPLICATION_CREDENTIALS=api-google-sheet-secret.json');
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

$service = new Google_Service_Sheets($client);

$spreadsheetId = '168RrRJ4wsdyg0X00AMWel2AQUApJs0YEyzzC_ILLDO4';
$range = 'Risposte del modulo 1!A1:G';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();
$num_values = count($values) - 1;
$last_spese = array();

if (empty($values)) {
} else {
    for ($i = $num_values; $i > $num_values - 5; $i--) {
    	$spesa_data = Carbon::createFromFormat('d/m/Y H.i.s', $values[$i][0]);
    	$spesa_tipo = $values[$i][2];
    	$spesa_note = (isset($values[$i][4])?' (' . $values[$i][4] . ")":'');
    	$spesa_costo = $values[$i][3];
        $last_spese[] = $spesa_data->format('d/m/Y') . ', ' . $spesa_tipo . $spesa_note . ', ' . $spesa_costo . " €";
    }
}

$spesetypes = json_decode(file_get_contents('spesetype.json'), true);

?>
<!DOCTYPE html>
<html>
<head>
	<title>Spese Casa</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
	<header class="text-center">
		<h1>Spese Casa</h1>
	</header>
	<section id="last_spese">
		<h2 class="text-center">Ultime spese segnate:</h1>
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
				<ul>
				<?php foreach ($last_spese as $ls) :?>
					<li><?php echo $ls ?></li>
				<?php endforeach; ?>
				</ul>
			</div>
			<div class="col-md-3"></div>
		</div>
	</section>
	<section id="form_spese">
		<h2 class="text-center">Inserisci nuova spesa</h1>
		<form action="#" method="POST">
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6 btn-group-justified btn-group-toggle" data-toggle="buttons">

		 			<?php foreach ($spesetypes as $st) : ?>
		 				<label class="btn btn-primary m-2">
		    				<input type="radio" required name="spesetype" value="<?php echo $st['code']; ?>" autocomplete="off"><?php echo $st['name']; ?>
		  				</label>
					<?php endforeach; ?>
				</div>
				<div class="col-md-3"></div>
			</div>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<fieldset class="mt-2">
						<label for="cost">Costo</label>
						<input type="number" required min="0" name="cost">
					</fieldset>
					<fieldset class="mt-2">
						<label for="notes">Note</label>
						<input type="textarea" name="notes">
					</fieldset>
					<fieldset class="mt-2">
						<input class="btn btn-success" type="submit" name="submit" value="Salva">
					</fieldset>
				</div>
				<div class="col-md-3"></div>
			</div>
		</form>
	</section>

	<script type="text/javascript">
		
	</script>

	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>