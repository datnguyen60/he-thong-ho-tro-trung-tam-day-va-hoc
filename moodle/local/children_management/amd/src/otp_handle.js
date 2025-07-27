define(["jquery", "core/ajax", "core/notification"], function (
  $,
  Ajax,
  Notification
) {
  var init = function () {
    var sendOtpButton = $("#send_otp_button");
    var emailField = $('[name="email"]'); // Lấy trường email
    var otpStatusMessage = $("#otp_status_message");

    sendOtpButton.on("click", function () {
      var email = emailField.val().trim();
      if (email === "") {
        Notification.warning("Please enter your email address first.");
        return;
      }

      sendOtpButton
        .prop("disabled", true)
        .val(M.util.get_string("sending", "local_children_management"));
      otpStatusMessage.text(""); // Xóa tin nhắn cũ

      // Tạo token bảo mật (sesskey)
      var sesskey = M.cfg.sesskey;

      // Gửi yêu cầu AJAX
      Ajax.call([
        {
          method: "core_local_children_management_send_otp", // Tên web service function (Xem Bước 6)
          args: { email: email, sesskey: sesskey },
          done: function (response) {
            if (response.status === "success") {
              otpStatusMessage
                .removeClass("text-danger")
                .addClass("text-success")
                .text(response.message);
              // Bạn có thể bắt đầu một bộ đếm ngược ở đây
              // Ví dụ: startCountdown(60);
            } else {
              otpStatusMessage
                .removeClass("text-success")
                .addClass("text-danger")
                .text(response.message);
            }
          },
          fail: Notification.exception, // Hiển thị lỗi từ server
          always: function () {
            sendOtpButton
              .prop("disabled", false)
              .val(M.util.get_string("sendotp", "local_children_management"));
          },
        },
      ]);
    });
  };

  return {
    init: init,
  };
});
