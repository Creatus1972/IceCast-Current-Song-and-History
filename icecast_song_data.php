<!DOCTYPE html">
<html>
    <head>
        <title>Icecast Now Playing / History Script</title>
    </head>
    <body>
        <h3 style="width: auto; text-align: center;">Icecast Now Playing / History (last 10 and maximum 20 played music)</h3>
        <?php require_once 'icecast.php'; ?>
        <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
        <script type="text/javascript">
            $(function () {
                setInterval(getTrackName, 10000);
            });
            function getTrackName() {
                $.ajax({
                    url: "icecast.php"
                })
                .done(function (data) {
                    $("#refresh").html(data);
                });
            }
            $(document).ready(function () {
                $("#message").on("click", function () {
                    $("#message").remove();
                });
            });
            setTimeout(function () {
                $('#message').remove();
            }, 5000);
        </script>
    </body>
</html>

