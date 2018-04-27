// TABBED NAV
function openTabContent (e, tabName) {
    var contentId = '#' + tabName + '-content';
    $('.tab-content').css('display', 'none');
    $('.tablinks.active').removeClass('active');
    $(contentId).css('display', 'block');
    $(e.currentTarget).addClass('active');
}

$('.tablinks').click((e) => {
    openTabContent(e, e.currentTarget.id);
});

// GENERATE OLAPIC FEED
$('#olapic-feed').click(function() {
    $('#response').fadeOut("slow");
    $('#message').html('');

  $.ajax({
    beforeSend: function() {
        $('#olapic-feed').attr('disabled', '').css('cursor', 'default');
        $('#ola').text('Generating...');
        $('.loading-olapic').show();
    },
    type: "POST",
    url: "php/olapicgen.php",
    complete: function() {
        $('#olapic-feed').removeAttr('disabled').css('cursor', 'pointer');
        $('#ola').text('Generate Feed');
        $('.loading-olapic').hide();
    }
  }).done(function( msg ) {
    $('#message').text(msg);
    $('#response').fadeIn("slow");
  });
});

// GENERATE GOOGLE FEED
$('#google-feed').click(function() {
  $('#response').fadeOut("slow");
  $('#message').html('');

  $.ajax({
    beforeSend: function() {
        $('#google-feed').attr('disabled', '').css('cursor', 'default');
        $('#goog').text('Generating...');
        $('.loading-google').show();
    },
    type: "POST",
    url: "php/googlegen.php",
    complete: function() {
        $('#google-feed').removeAttr('disabled', '').css('cursor', 'pointer');
        $('#goog').text('Generate Feed');
        $('.loading-google').hide();
    }
  }).done(function( msg ) {
    $('#message').text(msg);
    $('#response').fadeIn("slow");
  });
});

// CLOSE SUCCESS/ERROR MESSAGE
$('#close').click(function() {
  $('#response').fadeOut("slow");
});