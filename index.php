<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="/style.css" rel="stylesheet">
    <title>Document</title>
</head>
<body>
<div>Начало торгов: <input type="text" id="start" name="start" value="9.5"></div>
<div>Конец торгов: <input type="text" id="end" name="end" value="16"></div>
<button id="button">Получить</button>
<div>Время в MSK: <input type="text" id="time_msk" name="time_msk" value="" readonly></div>
<div>Время на биржах: <input type="text" id="time_usa" name="time_usa" value="" readonly></div>
<div class="message"><div class="red" id="indicator"></div>
    <div id="text"></div>
</div>

<script>
    function formatTime(seconds) {
        const d = Math.floor(seconds / 3600 / 24);
        console.log(d);
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.round(seconds % 60);
        return [
            d,
            h,
            m > 9 ? m : (h ? '0' + m : m || '0'),
            s > 9 ? s : '0' + s
        ].filter(Boolean).join(':');
    }

    function convert(unixtime){
        var months_arr = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
        var date = new Date(unixtime*1000);
        var year = date.getFullYear();
        var month = months_arr[date.getMonth()];
        var day = date.getDate();
        var hours = date.getHours();
        var minutes = "0" + date.getMinutes();
        var seconds = "0" + date.getSeconds();
        return  day + ' ' + month + ' ' + year + 'г. ' + hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);

    }

    function updateData() {
        clearInterval(interval)

        $.ajax({
            url: "get-time.php",
            method: 'POST',
            data: 'start=' + $('#start').val() + '&end=' + $('#end').val(),
            success: function (result) {
                var data = JSON.parse(result);
                i=0;
                interval = setInterval(function() {
                    if (data.time_rest < 100) {
                        updateData(); //если время вот-вот кончится, отправляем еще один запрос на сервер
                    }
                    if (data.type == 'end') {
                        $('#text').html('Торги на NYSE, NASDAQ закроются через: ' +
                            formatTime(data.time_rest - i) + ' по МСК');
                        $('#indicator').addClass('green');
                        $('#indicator').removeClass('red');
                    }
                    if (data.type == 'start') {
                        $('#text').html('Торги на NYSE, NASDAQ откроются через: ' +
                            formatTime(data.time_rest - i) + ' по МСК');
                        $('#indicator').addClass('red');
                        $('#indicator').removeClass('green');
                    }
                    $('#time_msk').val(convert(data.time_in_msk - i));
                    $('#time_usa').val(convert(data.time_in_usa - i));

                    i++;
                }, 1000);
            }
        });
    }

    var interval = null;



    $('#button').click(function() {
        updateData();

    });
</script>
</body>
</html>