$(document).ready(function () {
    let oldIds = [];
    let sendData = [];
	let oldIblockIds = [];
    $(".addalt-btn").on("click", function () {
        let ibIdVal = $(this).data('ids');
        if(typeof ibIdVal === 'undefined') {
            $('#result').html('Не выбран ни один Инфоблок');
        } else {
            // console.log(ibIdVal);
            if(oldIds.length == 0 && oldIblockIds.length == 0) {
                sendData = {
                    id: ibIdVal
                };
            }

            jQuery.ajax({
                url: '/local/modules/bitrixone.alt2img/admin/addAltsToImgInText.php', //Адрес подгружаемой страницы
                type: "POST", //Тип запроса
                dataType: "html", //Тип данных
                data: ({sendData: sendData}),
                success: function (response) { //Если все нормально
                    let resp = JSON.parse(response);

                    if(resp.result == 1) {
						$('#result').html('<span>'+resp.message+'</span>')
                        $.each(resp.ids, function( index, id ) {
                            oldIds.push(id);
							//console.log(id);
                        });
                        sendData = {
                            id: ibIdVal,
                            oldIds: oldIds,
							oldIblockIds: oldIblockIds
                        };
                        setTimeout(function(){
                            $(".addalt-btn").click()
                        }, 1000);
                    } else {
						$.each(resp.iblockIds, function( index, id ) {
                            oldIblockIds.push(id);
                        });
						if(oldIblockIds.length == ibIdVal.split(',').length){
							$('#result').html('<span>Alts успешно обновлены</span>')
						} else {
							$(".addalt-btn").click();
						}
					}
					
					/* setTimeout(function(){
                        $('#result').html('')
                    }, 4000); */
                },
                error: function (response) { //Если ошибка
                    $('#result').html('Form error');
                }
            });
        }
    })
})