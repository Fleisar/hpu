if(check_authorization() === false){
    binds_mlf()
}else{
    pgm.set("hub")
}

function binds_mlf(){
    $("#LFL").submit(function(e){
        if(!socket.connected){
            notification("Unable to connect to server.")
            return false
        }
        socket.emit("authorize",{
            type: 'traditional',
            username: $("#LFL-Username").val(),
            password: $("#LFL-Password").val()
        }).off('authorize').on('authorize',(d)=>{
            if(d.type !== 'response')
                return false
            if(!d.result){
                notification("Invalid username or password. (Failed to login)")
                return false
            }
            localStorage.setItem("sessionKey",d.session)
            localStorage.setItem("username",$("#LFL-Username").val())
            pgm.set("hub")
        })
        return false
    })
}
function check_authorization(){
    if(localStorage.getItem("sessionKey") === null)
        return false
    socket.emit("authorize",{
        type: 'session',
        session: localStorage.getItem("sessionKey"),
        username: localStorage.getItem("username")
    }).off('authorize').on("authorize",(d)=>{
        if(d.type !== 'response')
            return false
        if(!d.result)
            notification("Unable to authorize via session")
        return d.result
    })
}