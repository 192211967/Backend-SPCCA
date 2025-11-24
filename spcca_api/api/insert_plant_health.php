<?php
header("Content-Type: application/json");

// Include database connection
require_once "db.php";

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and retrieve form input
    $plant_name = trim($_POST['plant_name'] ?? 'Rosie');

    // Collect symptoms (convert to integer if empty)
    $leaf_yellowing = isset($_POST['leaf_yellowing']) ? (int)$_POST['leaf_yellowing'] : 0;
    $leaf_browning = isset($_POST['leaf_browning']) ? (int)$_POST['leaf_browning'] : 0;
    $leaf_pale_faded = isset($_POST['leaf_pale_faded']) ? (int)$_POST['leaf_pale_faded'] : 0;
    $leaf_dark_spots = isset($_POST['leaf_dark_spots']) ? (int)$_POST['leaf_dark_spots'] : 0;
    $leaf_holes_chewing = isset($_POST['leaf_holes_chewing']) ? (int)$_POST['leaf_holes_chewing'] : 0;
    $leaf_curling_edges = isset($_POST['leaf_curling_edges']) ? (int)$_POST['leaf_curling_edges'] : 0;
    $leaf_dry_crispy_edges = isset($_POST['leaf_dry_crispy_edges']) ? (int)$_POST['leaf_dry_crispy_edges'] : 0;
    $stem_soft_mushy = isset($_POST['stem_soft_mushy']) ? (int)$_POST['stem_soft_mushy'] : 0;
    $stem_dry_brittle = isset($_POST['stem_dry_brittle']) ? (int)$_POST['stem_dry_brittle'] : 0;
    $stem_discoloration = isset($_POST['stem_discoloration']) ? (int)$_POST['stem_discoloration'] : 0;
    $wilting_leaves = isset($_POST['wilting_leaves']) ? (int)$_POST['wilting_leaves'] : 0;
    $drooping_stems = isset($_POST['drooping_stems']) ? (int)$_POST['drooping_stems'] : 0;
    $overall_wilting = isset($_POST['overall_wilting']) ? (int)$_POST['overall_wilting'] : 0;

    // Check required field
    if (!empty($plant_name)) {
        
        // Check if the plant exists
        $check_stmt = $conn->prepare("SELECT * FROM plant_health WHERE plant_name = ?");
        $check_stmt->bind_param("s", $plant_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // **GENERATE POSSIBLE CAUSES & ACTION NEEDED**
            $possible_causes = [];
            $action_needed = [];

            if ($leaf_yellowing) {
                $possible_causes[] = "Nitrogen deficiency or overwatering";
                $action_needed[] = "Apply nitrogen-rich fertilizer and reduce watering";
            }
            if ($leaf_browning) {
                $possible_causes[] = "Underwatering or excessive heat";
                $action_needed[] = "Increase watering and provide shade if needed";
            }
            if ($leaf_pale_faded) {
                $possible_causes[] = "Iron deficiency";
                $action_needed[] = "Use iron supplement or acidic soil";
            }
            if ($leaf_dark_spots) {
                $possible_causes[] = "Fungal infection or overwatering";
                $action_needed[] = "Reduce watering and apply fungicide";
            }
            if ($leaf_holes_chewing) {
                $possible_causes[] = "Pest infestation (caterpillars, beetles)";
                $action_needed[] = "Use organic pesticides or remove pests manually";
            }
            if ($leaf_curling_edges) {
                $possible_causes[] = "Heat stress or viral infection";
                $action_needed[] = "Provide shade and ensure proper airflow";
            }
            if ($leaf_dry_crispy_edges) {
                $possible_causes[] = "Low humidity or salt buildup in soil";
                $action_needed[] = "Mist leaves and flush soil with clean water";
            }
            if ($stem_soft_mushy) {
                $possible_causes[] = "Root rot due to overwatering";
                $action_needed[] = "Reduce watering and improve drainage";
            }
            if ($stem_dry_brittle) {
                $possible_causes[] = "Severe dehydration or disease";
                $action_needed[] = "Increase watering and check for disease";
            }
            if ($stem_discoloration) {
                $possible_causes[] = "Bacterial or fungal infection";
                $action_needed[] = "Prune affected areas and apply treatment";
            }
            if ($wilting_leaves) {
                $possible_causes[] = "Underwatering or root damage";
                $action_needed[] = "Water deeply and check roots for damage";
            }
            if ($drooping_stems) {
                $possible_causes[] = "Lack of nutrients or overwatering";
                $action_needed[] = "Fertilize and adjust watering schedule";
            }
            if ($overall_wilting) {
                $possible_causes[] = "Extreme stress (drought, transplant shock)";
                $action_needed[] = "Provide stable conditions and hydration";
            }

            // Convert arrays to text for storage
            $possible_causes_text = implode("; ", $possible_causes) ?: "Unknown issue";
            $action_needed_text = implode("; ", $action_needed) ?: "Consult plant expert";

            // Update existing record
            $update_stmt = $conn->prepare("UPDATE plant_health SET 
                leaf_yellowing = ?, leaf_browning = ?, leaf_pale_faded = ?, 
                leaf_dark_spots = ?, leaf_holes_chewing = ?, leaf_curling_edges = ?, 
                leaf_dry_crispy_edges = ?, stem_soft_mushy = ?, stem_dry_brittle = ?, 
                stem_discoloration = ?, wilting_leaves = ?, drooping_stems = ?, 
                overall_wilting = ?, possible_causes = ?, action_needed = ?, 
                observation_date = NOW() 
                WHERE plant_name = ?");

            // Bind parameters
            $update_stmt->bind_param("iiiiiiiiiiiiisss", 
                $leaf_yellowing, $leaf_browning, $leaf_pale_faded, 
                $leaf_dark_spots, $leaf_holes_chewing, $leaf_curling_edges, 
                $leaf_dry_crispy_edges, $stem_soft_mushy, $stem_dry_brittle, 
                $stem_discoloration, $wilting_leaves, $drooping_stems, 
                $overall_wilting, $possible_causes_text, $action_needed_text, $plant_name
            );

            // Execute update query
            if ($update_stmt->execute()) {
                echo json_encode(["message" => "Plant health record updated successfully", "possible_causes" => $possible_causes_text, "action_needed" => $action_needed_text]);
            } else {
                echo json_encode(["error" => "Failed to update record", "details" => $update_stmt->error]);
            }

            // Close update statement
            $update_stmt->close();
        } else {
            echo json_encode(["error" => "Plant record not found"]);
        }

        // Close check statement
        $check_stmt->close();
    } else {
        echo json_encode(["error" => "Missing plant name"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}

// Close connection
$conn->close();
?>
