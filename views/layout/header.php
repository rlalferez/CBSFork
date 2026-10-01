<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .modal-body .form-label { font-size: 1.05rem; margin-bottom: 0.5rem; }
      @media print {
          @page { size: landscape; margin: 10mm; }
          body { overflow: visible !important; }
          .app-main { overflow: visible !important; height: auto !important; }
          .table-responsive { overflow: visible !important; }
          .no-print { display: none !important; }
      }
      /* Sidebar Mobile Toggle CSS */
      .app-sidebar { transition: transform 0.3s ease; z-index: 1040; }
      @media (max-width: 991.98px) {
          .app-sidebar {
              position: fixed; top: 0; left: 0; height: 100vh; width: 270px;
              transform: translateX(-100%); background: #fff;
          }
          .app-sidebar.show-sidebar { transform: translateX(0); }
      }
      
      /* Mobile Login Responsive Layout */
      @media (max-width: 767.98px) {
          .login-left-panel {
              height: auto !important;
              padding: 1.25rem 1rem !important;
          }
          .login-logo {
              width: 40px !important;
              height: 40px !important;
          }
          .login-logo span {
              font-size: 7px !important;
              line-height: 1 !important;
          }
          .login-title-1 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; margin-right: 0.3rem !important; }
          .login-title-2 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; }
          .login-subtitle { font-size: 0.7rem !important; letter-spacing: 0.5px !important; margin-top: 0.3rem !important; }
          .login-logo-gap { gap: 0.75rem !important; margin-bottom: 0.75rem !important; }
          .login-text-container { padding: 0 !important; max-width: 100% !important; margin-left: 0 !important; }
          .login-right-panel { height: calc(100vh - 120px) !important; justify-content: flex-start !important; padding-top: 2rem !important; }
      }
  </style>
</head>
<body>
