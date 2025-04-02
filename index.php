<?php
session_start();
    function minify($buffer) {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/<!--.*?-->/', '', preg_replace('/\s+/', ' ', $buffer))));
    }
    function minify_and_gzip($buffer) {
        return gzencode(minify($buffer), 9);
    }
    if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
        ob_start("minify_and_gzip");
        header("Content-Encoding: gzip");
    } else {
        ob_start("minify");
    }
    require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Shorten your URLs effortlessly with Shorten. Create compact, easy-to-share links and track click statistics for each link you generate. Join us to enhance your sharing experience and make your links more memorable!">
    <meta name="author" content="Shorten">
    <meta name="keywords" content="URL shortener, link shortener, shorten links, URL shortening service, trackable links, shareable links, free URL shortener, custom URL shortener, link management, analytics, click tracking, QR code generator, social media sharing, branded links, link customization, URL redirection, link statistics, URL shortening API, mobile-friendly links, link sharing tools, URL shortener with analytics">
    <meta http-equiv="Content-Language" content="en">
    <link rel="canonical" href="https://shorten.rf.gd">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="Shorten - Your URL Shortening Service">
    <meta property="og:description" content="Shorten your URLs effortlessly with Shorten. Create compact, easy-to-share links and track click statistics for each link you generate.">
    <meta property="og:image" content="URL.png">
    <meta property="og:url" content="https://shorten.rf.gd">
    <meta property="og:type" content="website">
    <title>URL Shortener</title>
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
</head>
<body>
    <style>body { margin: 0; padding: 0; height: 100%; width: 100%; background-color: black; color: white; transition: 0.5s; font-family: Arial, sans-serif; }</style>
    <?php
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $protocol . $_SERVER['HTTP_HOST'];
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $original_url = trim($conn->real_escape_string($_POST['original_url']));
            $custom_alias = trim($conn->real_escape_string($_POST['custom_alias']));
            if (!preg_match("/^(http:\/\/|https:\/\/)/", $original_url)) {
                $original_url = "https://" . $original_url;
            }
            if (!filter_var($original_url, FILTER_VALIDATE_URL) || !preg_match("/\.[a-z]{2,}$/i", parse_url($original_url, PHP_URL_HOST))) {
                $response = "Invalid URL format";
            } else {
                $sql = "SELECT * FROM links WHERE short_code='$custom_alias'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $response = "Custom alias already taken.";
                } else {
                    $sql = "INSERT INTO links (original_url, short_code) VALUES ('$original_url', '$custom_alias')";
                    if ($conn->query($sql) === TRUE) {
                        $response = "Shortened URL: <a href='/$custom_alias' target='_blank' style='color:DodgerBlue;'>$domain/$custom_alias</a>";
                    } else {
                        $response = "Error: " . $conn->error;
                    }
                }
            }
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(["response" => $response]);
            exit;
        }
        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $code = $conn->real_escape_string($_GET['code']);
            $sql = "SELECT original_url FROM links WHERE short_code='$code'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $redirect_url = htmlspecialchars($row['original_url'], ENT_QUOTES, 'UTF-8');
                echo "<button style='background-color: #808080;border: none;color: white;text-align: center;text-decoration: none;display: flex;justify-content: center;align-items: center;font-size: 7vw;cursor: pointer;width: 100vw;height: 100vh;' onclick=\"location.href='$redirect_url';location.href='$redirect_url';location.href='$redirect_url';\">Click for next</button>
                <script>setTimeout(() => location.href='$redirect_url', 1000);</script>";
                exit;
            } else {
                echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11' fetchpriority='low'></script>
                <script>Swal.fire({theme: 'dark', icon: 'error', title: 'URL not found!', text: 'Please input correct alias in the address bar!', confirmButtonText: 'OK'}).then(() => location.reload());</script>";
            }
        } else {
    ?>
        <style>
            .switch-button {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: transparent;
                color: white;
                border: none;
                padding: 10px 10px;
                cursor: pointer;
                border-radius: 100%;
                z-index: 1061;
            }
            .switch-button:hover {
                background-color: gray;
            }
            footer {
                position: fixed;
                left: 0;
                bottom: 0;
                width: 100%;
                background-color: #1B2631;
                color: white;
                text-align: center;
                z-index: 1061;
            }
            button {
                display: flex; 
                align-items: center; 
                justify-content: center;
            }
            #themeIconMoon, #themeIconSun {
                transition: opacity 0.5s ease;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" fetchpriority="low"></script>
        <script>curTheme = 'dark';</script>
        <button class="switch-button" id="themeSwitch">
            <img src="moon-solid.svg" alt="" id="themeIconMoon" style="width: 2rem; height: 2rem; filter: invert(1); opacity: 1;" fetchpriority="low">
            <img src="sun-solid.svg" alt="" id="themeIconSun" style="width: 2rem; height: 2rem; filter: invert(0); opacity: 0; position: absolute;" fetchpriority="low">
        </button>
        <script>
            let isDarkMode = true;
            document.getElementById('themeSwitch').addEventListener('click', () => {
                document.body.style.backgroundColor = isDarkMode ? 'white' : 'black';
                document.body.style.color = isDarkMode ? 'black' : 'white';
                document.querySelector('footer').style.backgroundColor = isDarkMode ? '#e0e0e0' : '#1B2631';
                document.querySelector('footer').style.color = isDarkMode ? 'black' : 'white';
                document.getElementById('themeIconMoon').style.opacity = isDarkMode ? 0 : 1;
                document.getElementById('themeIconSun').style.opacity = isDarkMode ? 1 : 0;
                curTheme = isDarkMode ? 'light' : 'dark';
                Swal.update({
                    theme: curTheme,
                });
                isDarkMode = !isDarkMode;
            });
            function showModal() {
                Swal.fire({
                    theme: curTheme,
                    title: 'Shorten Your URL',
                    html:
                        '<div style="max-width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">' + 
                        '<input id="original_url" class="swal2-input" placeholder="Enter URL">' + 
                        '<input id="custom_alias" class="swal2-input" placeholder="Enter Alias">' + 
                        '</div>',
                    focusConfirm: false,
                    showCancelButton: true,
                    allowOutsideClick: false, 
                    confirmButtonText: 'Shorten',
                    preConfirm: () => {
                        const original_url = document.getElementById('original_url').value;
                        const custom_alias = document.getElementById('custom_alias').value;
                        if (!original_url || !custom_alias) Swal.showValidationMessage('Please enter both URL and Custom Alias');
                        return { original_url, custom_alias };
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        showModal();
                    } else {
                        fetch(window.location.href, {
                            method: 'POST',
                            body: new URLSearchParams(result.value)
                        })
                        .then(response => response.json())
                        .then((data) => {
                            Swal.fire({
                                title: 'Result',
                                theme: curTheme,
                                html: data.response,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                showModal();
                            });
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                theme: curTheme,
                                title: 'Error',
                                text: error
                            }).then(() => {
                                showModal();
                            });
                        });
                    }
                });
            }
            window.onload = showModal;
        </script>
        <footer><p>2025 - Project created by Oka</p></footer>
<?php } ?>
</body>
</html>
<?php 
$conn->close();
ob_end_flush(); 
?>