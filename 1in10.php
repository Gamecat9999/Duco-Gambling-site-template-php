<?php
$DUINOCOIN_API_URL = "https://server.duinocoin.com";
$FAUCET_USERNAME = "ENTER_USERNAME_HERE";
$FAUCET_PASSWORD = "ENTER_PASSWORD_HERE";
$BET_AMOUNT = 2.5; // Changed to 2.5 DUCO
$WIN_AMOUNT = 5.0; // Changed to 5.0 DUCO

function transfer_duinocoin($sender_username, $sender_password, $recipient_username, $amount) {
    global $DUINOCOIN_API_URL;
    $url = "$DUINOCOIN_API_URL/transaction/?username=" . urlencode($sender_username) 
         . "&password=" . urlencode($sender_password) 
         . "&recipient=" . urlencode($recipient_username) 
         . "&amount=" . urlencode($amount) 
         . "&memo=1in10guess";
    
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        $error = error_get_last();
        error_log("Transaction failed: " . $error['message']);
        return FALSE;
    }
    
    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['guess'])) {
    header('Content-Type: application/json');
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $guess = intval($_POST['guess']);

    // Verify guess is within range
    if ($guess < 1 || $guess > 10) {
        echo json_encode(array("error" => "Invalid guess. Please choose a number between 1 and 10"));
        http_response_code(400);
        exit;
    }

    // First verify credentials with initial bet
    $verify = transfer_duinocoin($username, $password, $FAUCET_USERNAME, $BET_AMOUNT);
    if (!$verify) {
        echo json_encode(array("error" => "Invalid credentials or insufficient balance"));
        http_response_code(400);
        exit;
    }

    // Generate winning number
    $winning_number = rand(1, 10);

    if ($guess == $winning_number) {
        // User wins - return bet amount plus winnings
        $result = transfer_duinocoin($FAUCET_USERNAME, $FAUCET_PASSWORD, $username, $WIN_AMOUNT);
        if ($result) {
            echo json_encode(array(
                "result" => "win", 
                "number" => $winning_number,
                "amount" => $WIN_AMOUNT
            ));
        } else {
            echo json_encode(array("error" => "Transaction failed. Please contact support."));
            http_response_code(500);
        }
    } else {
        // User loses - they already sent the bet amount
        echo json_encode(array(
            "result" => "lose", 
            "number" => $winning_number,
            "amount" => $BET_AMOUNT
        ));
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DuinoCoin Number Game (1-10)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ... keep existing styles ... -->
    <style>/* Add this to the <style> section of each game */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: #fff;
}

.container {
    max-width: 600px;
    margin: 20px auto;
    padding: 25px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

h1 {
    color: #ffcc00;
    text-align: center;
    font-size: 2.2em;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.info {
    text-align: center;
    color: #fff;
    margin-bottom: 25px;
    padding: 15px;
    background: rgba(255, 204, 0, 0.1);
    border-radius: 10px;
    border: 1px solid rgba(255, 204, 0, 0.2);
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: #ffcc00;
    font-weight: bold;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 2px solid rgba(255, 204, 0, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-size: 16px;
    transition: all 0.3s ease;
}

input:focus, select:focus {
    outline: none;
    border-color: #ffcc00;
    box-shadow: 0 0 10px rgba(255, 204, 0, 0.3);
}

button {
    width: 100%;
    padding: 15px;
    background: #ffcc00;
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
}

button:hover {
    background: #ffd700;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 204, 0, 0.3);
}

button:disabled {
    background: #666;
    cursor: not-allowed;
    transform: none;
}

#result {
    margin-top: 20px;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    display: none;
}

.win {
    background: rgba(39, 174, 96, 0.2);
    border: 1px solid #27ae60;
    color: #2ecc71;
}

.lose {
    background: rgba(231, 76, 60, 0.2);
    border: 1px solid #e74c3c;
    color: #e74c3c;
}

.error {
    background: rgba(231, 76, 60, 0.2);
    border: 1px solid #e74c3c;
    color: #e74c3c;
}

.footer-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 25px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.disclaimer {
    padding: 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    margin-bottom: 20px;
}

.disclaimer h3 {
    color: #ffcc00;
    margin-bottom: 15px;
    font-size: 1.2em;
    text-align: center;
}

.disclaimer p {
    color: #fff;
    line-height: 1.6;
    margin-bottom: 10px;
    text-align: center;
}

.technical-note {
    font-size: 0.9em;
    color: #aaa;
    font-style: italic;
}

.copyright {
    text-align: center;
    color: #aaa;
    font-size: 0.9em;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .container, .footer-container {
        margin: 10px;
        padding: 20px;
    }
    
    h1 {
        font-size: 1.8em;
    }
    
    button {
        padding: 12px;
        font-size: 16px;
    }
}</style>
</head>
<body>
    <div class="container">
        <h1>DuinoCoin Number Game (1-10)</h1>
        <p class="info">
            Bet 2.5 DUCO to play. Win 5 DUCO if you guess correctly!<br>
            <small>Make sure you have enough balance before playing.</small>
        </p>
        <form id="guessForm">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="guess">Your Guess (1-10):</label>
                <input type="number" id="guess" name="guess" min="1" max="10" required>
            </div>
            <button type="submit" id="submitBtn">Play (Bet 2.5 DUCO)</button>
        </form>
        <div id="result"></div>
    </div>

    <div class="footer-container">
        <div class="footer-content">
            <div class="disclaimer">
                <h3>Fair Play Guarantee</h3>
                <p>This script is completely random with no unfavorable odds. It is not rigged nor a scam. 
                The source code is available on GitHub for transparency.</p>
                <p class="technical-note">Note: You will need a PHP server to host it yourself.</p>
            </div>
            <div class="copyright">
                <p>&copy; 2024 katfaucet</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('guessForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const resultDiv = document.getElementById('result');
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing Bet...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                resultDiv.style.display = 'block';
                if (data.error) {
                    resultDiv.className = 'error';
                    resultDiv.textContent = data.error;
                } else {
                    resultDiv.className = data.result === 'win' ? 'win' : 'lose';
                    if (data.result === 'win') {
                        resultDiv.textContent = `The number was ${data.number}! You won! Received ${data.amount} DUCO`;
                    } else {
                        resultDiv.textContent = `The number was ${data.number}! You lost ${data.amount} DUCO`;
                    }
                }
            } catch (error) {
                resultDiv.style.display = 'block';
                resultDiv.className = 'error';
                resultDiv.textContent = 'An error occurred. Please try again.';
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Play (Bet 2.5 DUCO)';
            }
        });

        // Add input validation
        document.getElementById('guess').addEventListener('input', function(e) {
            const value = parseInt(this.value);
            if (value < 1) this.value = 1;
            if (value > 10) this.value = 10;
        });
    </script>
</body>
</html>