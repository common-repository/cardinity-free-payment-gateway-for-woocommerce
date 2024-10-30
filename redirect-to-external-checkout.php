<!DOCTYPE html>
<html lang="en">
   <head >
      <title>Credit/Debit Card Payment By Cardinity</title>
   </head>
   <body onload="document.forms['checkout'].submit()" style="background-color: #f2f9ff; color: #6e8094; margin: 0;">
       <div style="display: flex; justify-content: center; align-items: center; font-family: PT Sans,sans-serif; height: 100vh; flex-direction: column;">
            <div style="font-size: 4.56rem; font-weight: 700;">— Payment Handled By Cardinity —</div>
            <p>Click the button if you are not redirected automatically</p>
            <form name="checkout" method="POST" action="https://checkout.cardinity.com">
                <button type=submit>Click Here</button>
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars(strip_tags($_GET['amount'])) ?>" />
                <input type="hidden" name="country" value="<?php echo htmlspecialchars(strip_tags($_GET['country'])) ?>" />
                <input type="hidden" name="currency" value="<?php echo htmlspecialchars(strip_tags($_GET['currency'])) ?>" />
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars(strip_tags($_GET['order_id'])) ?>" />
                <input type="hidden" name="description" value="<?php echo htmlspecialchars(strip_tags($_GET['description'])) ?>" />
                <?php
                if (isset($_GET['email_address'])){
                    ?>
                    <input type="hidden" name="email_address" value="<?php echo htmlspecialchars(strip_tags($_GET['email_address'])) ?>" />
                    <?php
                }
                if (isset($_GET['mobile_phone_number'])){
                    ?>
                    <input type="hidden" name="mobile_phone_number" value="<?php echo htmlspecialchars(strip_tags($_GET['mobile_phone_number'])) ?>" />
                    <?php
                }
                ?>
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars(strip_tags($_GET['return_url'])) ?>" />
                <input type="hidden" name="notification_url" value="<?php echo htmlspecialchars(strip_tags($_GET['notification_url'])) ?>" />
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars(strip_tags($_GET['project_id'])) ?>" />
                <input type="hidden" name="signature" value="<?php echo htmlspecialchars(strip_tags($_GET['signature'])) ?>" />
            </form>
       </div>
   </body>
</html>
