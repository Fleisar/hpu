let JQInit = false
let hpu = {
    messages: []
}
const socket = io('http://127.0.0.1:813')
$(()=>{
    JQInit = true
    console.log("[JQuery] Initialized.")
    pgm.set("login")
})
socket.on('connect', ()=>{
    console.log("[Socket.IO] Connected.")
    check_authorization()
}).on('error', (error)=>{
    console.error("[Socket.IO] Connection thrown an error.", error)
}).on('reconnect', ()=>{
    console.info("[Socket.IO] Reconnected.")
}).on('reconnect_attempt', (attempt)=>{
    console.log("[Socket.IO] Trying to reconnect ("+attempt+").")
}).on('reconnect_failed', ()=>{
    console.error("[Socket.IO] Reconnection failed.")
})
const pgm = {
    config: [],
    content: undefined,
    list: ['login','hub','messages'],
    curreth: null,
    get(){
        return this.curreth
    },
    set(page){
        if(this.list.find(sPage=>sPage===page) === undefined){
            console.error("[PGM] This page doesn't exists!")
            return false
        }
        $("._body").hide()
        $.ajax("pages/"+page+".html",{
            success: (d)=>{
                console.log("[PGM] Content of \""+page+"\" was loaded successful.")
                $.ajax("pages/"+page+".json",{
                    success: (j)=>{
                        console.log("[PGM] Config of \""+page+"\" was loaded successful.")
                        this.config = j
                        this.content = d
                        this.parser()
                    },
                    error(e){
                        console.error("[PGM] Unable to load \""+page+"\".")
                    }
                })
            },
            error(e){
                console.error("[PGM] Unable to load \""+page+"\".")
            }
        })
    },
    parser(){
        $("._environment").html("")
        $("._body").attr("class","_body fullscreen").html(this.content)
        if(this.config.scripts !== undefined) if(this.config.scripts.length !== 0)
            this.config.scripts.forEach((item,index)=>{
                $("._environment").append(`<script src="${item}"></script>`)
            })
        if(this.config.styles !== undefined) if(this.config.styles.length !== 0)
            this.config.styles.forEach((item,index)=>{
                $("._environment").append(`<link href="${item}" rel="stylesheet">`)
            })
        if(this.config.classes !== undefined) if(this.config.classes.length !== 0)
            this.config.classes.forEach((item,index)=>{
                $("._body").addClass(item)
            })
        $("._body").show()
    }
}

function notification(text){
    let time = (new Date()).getTime()
    $("._notifications").append("<div class=\"notification notif-"+time+"\">"+text+"</div>")
    setTimeout(()=>{
        $(".notif-"+time).remove()
    },3e3)
}
function dateFormer(unix){
    let t = new Date(unix)
    return (t.getDate()>=10?t.getDate():"0"+t.getDate())+"."+
        (t.getMonth()>=10?t.getMonth():"0"+t.getMonth())+"."+
        t.getFullYear()+" "+
        (t.getHours()>=10?t.getHours():"0"+t.getHours())+":"+
        (t.getMinutes()>=10?t.getMinutes():"0"+t.getMinutes())+":"+
        (t.getSeconds()>=10?t.getSeconds():"0"+t.getSeconds())
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