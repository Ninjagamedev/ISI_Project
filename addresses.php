<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/AddressManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$address_manager = new AddressManager($auth->getCurrentUser()['id']);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action']) {
            case 'add':
                $address_manager->addAddress($_POST);
                $success_message = 'Address added successfully!';
                break;
            case 'update':
                $address_manager->updateAddress($_POST['address_id'], $_POST);
                $success_message = 'Address updated successfully!';
                break;
            case 'delete':
                $address_manager->deleteAddress($_POST['address_id']);
                $success_message = 'Address deleted successfully!';
                break;
            case 'set_default':
                $address_manager->setDefaultAddress($_POST['address_id']);
                $success_message = 'Default address updated successfully!';
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$addresses = $address_manager->getAddresses();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addresses.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="addresses-page">
            <h1>My Addresses</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Add New Address Button -->
            <button class="btn btn-primary" onclick="showAddressForm()">Add New Address</button>

            <!-- Address Form (Hidden by default) -->
            <div id="address-form" class="address-form" style="display: none;">
                <h2>Add New Address</h2>
                <form method="POST" id="add-address-form">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label for="address_line1">Address Line 1 *</label>
                        <input type="text" name="address_line1" required>
                    </div>

                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" name="address_line2">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" name="city" required>
                        </div>

                        <div class="form-group">
                            <label for="state">State/Province *</label>
                            <input type="text" name="state" required>
                        </div>

                        <div class="form-group">
                            <label for="postal_code">Postal Code *</label>
                            <input type="text" name="postal_code" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="country">Country *</label>
                        <select name="country" required>
                            <option value="">Select Country</option>
                            <?php foreach (COUNTRIES as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_default" value="1">
                            Set as default address
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Address</button>
                        <button type="button" class="btn btn-secondary" onclick="hideAddressForm()">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Addresses List -->
            <div class="addresses-list">
                <?php if ($addresses): ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                            <?php if ($address['is_default']): ?>
                                <div class="default-badge">Default</div>
                            <?php endif; ?>
                            
                            <div class="address-content">
                                <h3><?php echo htmlspecialchars($address['full_name']); ?></h3>
                                <p class="phone"><?php echo htmlspecialchars($address['phone']); ?></p>
                                <p class="address">
                                    <?php echo htmlspecialchars($address['address_line1']); ?>
                                    <?php if ($address['address_line2']): ?>
                                        <br><?php echo htmlspecialchars($address['address_line2']); ?>
                                    <?php endif; ?>
                                    <br><?php echo htmlspecialchars($address['city']); ?>, 
                                    <?php echo htmlspecialchars($address['state']); ?> 
                                    <?php echo htmlspecialchars($address['postal_code']); ?>
                                    <br><?php echo htmlspecialchars($address['country']); ?>
                                </p>
                            </div>

                            <div class="address-actions">
                                <?php if (!$address['is_default']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="btn btn-secondary">Set as Default</button>
                                    </form>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary" 
                                        onclick="editAddress(<?php echo htmlspecialchars(json_encode($address)); ?>)">
                                    Edit
                                </button>
                                
                                <?php if (!$address['is_default']): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this address?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-addresses">
                        <p>You haven't added any addresses yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script>
    function showAddressForm() {
        document.getElementById('address-form').style.display = 'block';
        document.getElementById('add-address-form').reset();
        document.getElementById('add-address-form').action.value = 'add';
    }

    function hideAddressForm() {
        document.getElementById('address-form').style.display = 'none';
    }

    function editAddress(address) {
        const form = document.getElementById('add-address-form');
        form.action.value = 'update';
        form.querySelector('h2').textContent = 'Edit Address';
        
        // Fill form fields
        form.full_name.value = address.full_name;
        form.phone.value = address.phone;
        form.address_line1.value = address.address_line1;
        form.address_line2.value = address.address_line2 || '';
        form.city.value = address.city;
        form.state.value = address.state;
        form.postal_code.value = address.postal_code;
        form.country.value = address.country;
        form.is_default.checked = address.is_default;

        // Add address ID for update
        const addressIdInput = document.createElement('input');
        addressIdInput.type = 'hidden';
        addressIdInput.name = 'address_id';
        addressIdInput.value = address.id;
        form.appendChild(addressIdInput);

        showAddressForm();
    }
    </script>
</body>
</html>
