
msg_init(()=>{
    binds_msg()
    msg_passive_mode()
})
function msg_passive_mode(){
    socket.off("receiveMessage").on("receiveMessage",(d)=>{
        d.sent = (new Date()).getTime()/1000
        hpu.messages.data.push(d)
        dialog_update(hpu.messages.currDialog)
    })
}
function dialog_update(user){
    $(".messages").html("")
    hpu.messages.data.forEach((item,index)=>{
        let style = `
                --text: "${item.from} | ${dateFormer(item.sent*1000)}";
            `
        if(item.from === user && item.to === localStorage.getItem("username")){
            $('.messages').append("<div class='message messageFrom' style='"+style+"'>"+item.content+"</div>")
        }else if(item.to === user && item.from === localStorage.getItem("username"))
            $('.messages').append("<div class='message messageTo' style='"+style+"'>"+item.content+"</div>")
    })
}
function binds_msg(){
    $(".userDMLink").unbind("click").click(function(){
        $(".userDMLink").removeClass("selected")
        $(this).addClass("selected")
        hpu.messages.currDialog = $(this).attr("data-user")
        dialog_update(hpu.messages.currDialog)
    })
    $(".dialog form").submit(function(){
        let content = $(".inputMessage").val()
        socket.emit("sendMessage",{
            username_AX: localStorage.getItem("username"),
            user_to: hpu.messages.currDialog,
            content: content
        }).once("sendMessage",(d)=>{
            if(d.type !== "response")
                return false
            $(".inputMessage").val("")
            hpu.messages.data.push({
                from: localStorage.getItem("username"),
                to: hpu.messages.currDialog,
                content: content,
                sent: (new Date()).getTime()/1000
            })
            dialog_update(hpu.messages.currDialog)
        })
        return false
    })
}
function msg_init(callback){
    if(typeof callback !== "function"){
        console.log("[Messages] Callback should be a function.")
        return false
    }
    $.ajax({
        url: "../api/messages",
        type: "GET",
        headers: {
            username_AX: localStorage.getItem("username"),
            session_AX: localStorage.getItem("sessionKey"),
            to: localStorage.getItem("username"),
            start_from: (new Date()).getTime()/1000-30*24*60*60
        },
        success(d){
            let user = localStorage.getItem("username")
            let users = []
            hpu.messages.data = d.messages
            d.messages.forEach((item,index)=>{
                if(
                    users.find((value)=>value===item.from) === undefined ||
                    users.find((value)=>value===item.to) === undefined
                ){
                    let newU = users.find((value)=>value===item.from) === undefined?item.from:item.to
                    users.push(newU)
                    $(".people").append("<div class=\"userDMLink\" data-user=\""+newU+"\">"+newU+"</div>")
                }
            })
            hpu.messages.users = users
            callback(d)
        }
    })
}