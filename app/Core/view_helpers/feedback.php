<?php
// get the feedback (they are arrays, to make multiple positive/negative messages possible)

use App\Core\Http\Session;

$feedback_positive = App::getRequest()->session->get('feedback_positive');
$feedback_negative =  App::getRequest()->session->get('feedback_negative');

// echo out positive messages
if (isset($feedback_positive)) {
	$result = '<div class="alert alert-success alert-dismissible" id="window-alerts">';
    $result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    foreach ($feedback_positive as $feedback) {
        $result .= $feedback . '<br>';
    }
    $result .= '</div>';
    echo $result;
}

// echo out negative messages
if (isset($feedback_negative)) {
    $result = '<div class="alert alert-danger alert-dismissible"  id="window-alerts">';
    $result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    foreach ($feedback_negative as $feedback) {
        $result .= $feedback . '<br>';
    }
    $result .= '</div>';
    echo $result;
}