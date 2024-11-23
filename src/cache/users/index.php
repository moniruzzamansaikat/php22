<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">

      <a href="/" class="navbar-brand">Brand</a>

      <ul class="navbar-nav ms-auto">
        <a class="nav-link" href="/">Home</a>
        <a class="nav-link" href="/users">Users</a>
        <a class="nav-link" href="/products">Products Json</a>
      </ul>
  </nav>
  </div>

  <main class="container">
    <h2><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></h2>

    <?php if ($message = Php22\Utils\Flash::get('success')): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($message = Php22\Utils\Flash::get('username')): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($message = Php22\Utils\Flash::get('password')): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>



    <div class="card mb-3">
        <div class="card-body">
            <form method="post" action="/users">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" autofocus name="username" id="username" />
                <button class="btn btn-sm btn-primary mt-2" type="submit">Submit</button>
            </form>
        </div>
    </div>

    <ul>
        <?php foreach ($users as $user): ?>
            <li><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if (count($users) === 0): ?>
        <p>No users found.</p> 
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>