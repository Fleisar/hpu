const express = require("express")
const app = express()
const http = require('http').createServer(app)
const io = require('socket.io')(http, {
    cors: {
        origin: "http://127.0.0.1"
    }
})
const mysql = require('mysql')
const CONFIG = require('./config')

let users = {}
let AX_users = {}

let sql = mysql.createConnection({
    host: CONFIG.sql.ip,
    user: CONFIG.sql.user,
    password: CONFIG.sql.password,
    port: CONFIG.sql.port
})
sql.connect((error) => {
    if(error) {
        console.error(error)
        return
    }
    console.log('SQL connected!')
})
io.on('connection', (socket) => {
    socket.emit('connectionCheck', (new Date()).getTime())
    socket.on('disconnect',()=>{
        if(Object.values(AX_users).indexOf(socket.id) !== -1){
            let user = Object.keys(AX_users)[Object.values(AX_users).indexOf(socket.id)]
            console.log(user+" was disconnected.")
            socket.broadcast.emit("onlineMng",{
                type: "offline",
                user: user
            })
        }

        users[socket.id] = undefined
    })
    users[socket.id] = socket
    // only for checking
    socket.on('AuthRequest', (d) => {
        sql.query('SELECT `password` FROM `'+CONFIG.sql.database+'`.`'+CONFIG.db.users+'` WHERE `username`=?', [
            d.username
        ],(error,data)=>{
            if(error){
                console.error(error)
                return
            }
            if(data.length === 0)
                socket.emit('AuthResponse',{state:false,description:'user not found.'})
            socket.emit('AuthResponse',{state:(MD5(d.password) === data[0].password)})
        })
    })
    socket.on('authorize',(d)=>{
        switch (d.type){
            case 'traditional':
                if(d.username === undefined || d.password === undefined)
                    socket.emit('authorize',{
                        type: 'response',
                        result: false,
                        code: 0
                    })
                let time = (new Date()).getTime()
                let session = MD5(d.username+":"+MD5(d.password)+":"+time)+"/"+time
                sql.query('SELECT `password` FROM `'+CONFIG.sql.database+'`.`'+CONFIG.db.users+'` WHERE `username`=? LIMIT 1', [
                    d.username
                ], (error,data)=>{
                    if(error){
                        console.error(error)
                        socket.emit('authorize',{type:'response',result:false,code:1})
                    }
                    if(data.length === 0 || MD5(d.password) !== data[0].password){
                        socket.emit('authorize',{type:'response',result:false,code:2})
                        return
                    }
                    sql.query('UPDATE `'+CONFIG.sql.database+'`.`'+CONFIG.db.users+'` SET `session`=? WHERE `username`=?', [
                        session,
                        d.username
                    ], (error,data)=>{
                        if(error){
                            console.error(error)
                            socket.emit('authorize',{type:'response',result:false,code:3})
                        }
                        AX_users[d.username] = socket.id
                        socket.broadcast.emit("onlineMng",{
                            type: "online",
                            user: d.username
                        })
                        console.log(d.username+" was authorized.")
                        socket.emit('authorize',{
                            type: 'response',
                            result: true,
                            session: session
                        })
                    })
                })
                break;
            case 'session':
                if(d.username === undefined || d.session === undefined)
                    socket.emit('authorize',{type:'response',result:false,code:4})
                let sessionTime = d.session.split("/")[1]
                sql.query('SELECT `password` FROM `'+CONFIG.sql.database+'`.`'+CONFIG.db.users+'` WHERE `username`=? AND `session`=? LIMIT 1', [
                    d.username,
                    d.session
                ], (error,data)=>{
                    if(error){
                        console.error(error)
                        socket.emit('authorize',{type:'response',result:false,code:5})
                    }
                    if(d.session !== MD5(d.username+":"+data[0].password+":"+sessionTime)+"/"+sessionTime)
                        socket.emit('authorize',{type:'response',result:false,code:6})
                    AX_users[d.username] = socket.id
                    console.log(d.username+" was authorized.")
                    socket.broadcast.emit("onlineMng",{
                        type: "online",
                        user: d.username
                    })
                    socket.emit('authorize',{
                        type: 'response',
                        result: true
                    })
                })
                break;
        }
    }).on('sendMessage',(d)=>{
        if(AX_users[d.username_AX] !== socket.id || d.username_AX === undefined)
            socket.emit('sendMessage',{
                type: 'response',
                result: false,
                reason: 'Refused due to authorization.'
            })
        if(AX_users[d.user_to] === undefined)
            socket.emit('sendMessage',{
                type: 'response',
                result: false,
                reason: 'This user is currently offline.'
            })
        if(d.content.length === 0 || d.content.length > 300)
            socket.emit('sendMessage',{
                type: 'response',
                result: false,
                reason: 'Message too short or too long.'
            })
        users[AX_users[d.user_to]].emit('receiveMessage',{
            from: d.username_AX,
            to: d.user_to,
            content: d.content
        })
        socket.emit('sendMessage',{
            type: 'response',
            result: true
        })
    }).on("onlineMng",(d)=>{
        if(d.type !== "request-status")
            socket.emit("onlineMng",{
                type: 'response',
                result: false
            })
        if(d.username === undefined)
            socket.emit("onlineMng",{
                type: 'response',
                result: false
            })
        socket.emit("onlineMng",{
            type: AX_users[d.username] !== undefined?"online":"offline",
            user: d.username
        })
    })
})

app.get("/", (req, res) => {
    res.sendFile(__dirname+"/_index.html")
})

http.listen(813, () => {console.log("Listening on 813")})

function zip(string) {
    if(typeof string !== 'string')
        return false;
    let binary = new Uint8Array(parseInt(String, 8))
    return pako.deflate(binary)
}
let MD5=function(d){d=unescape(encodeURIComponent(d));result=M(V(Y(X(d),8*d.length)));return result.toLowerCase()};function M(d){for(var _,m="0123456789ABCDEF",f="",r=0;r<d.length;r++)_=d.charCodeAt(r),f+=m.charAt(_>>>4&15)+m.charAt(15&_);return f}function X(d){for(var _=Array(d.length>>2),m=0;m<_.length;m++)_[m]=0;for(m=0;m<8*d.length;m+=8)_[m>>5]|=(255&d.charCodeAt(m/8))<<m%32;return _}function V(d){for(var _="",m=0;m<32*d.length;m+=8)_+=String.fromCharCode(d[m>>5]>>>m%32&255);return _}function Y(d,_){d[_>>5]|=128<<_%32,d[14+(_+64>>>9<<4)]=_;for(var m=1732584193,f=-271733879,r=-1732584194,i=271733878,n=0;n<d.length;n+=16){var h=m,t=f,g=r,e=i;f=md5_ii(f=md5_ii(f=md5_ii(f=md5_ii(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_ff(f=md5_ff(f=md5_ff(f=md5_ff(f,r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+0],7,-680876936),f,r,d[n+1],12,-389564586),m,f,d[n+2],17,606105819),i,m,d[n+3],22,-1044525330),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+4],7,-176418897),f,r,d[n+5],12,1200080426),m,f,d[n+6],17,-1473231341),i,m,d[n+7],22,-45705983),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+8],7,1770035416),f,r,d[n+9],12,-1958414417),m,f,d[n+10],17,-42063),i,m,d[n+11],22,-1990404162),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+12],7,1804603682),f,r,d[n+13],12,-40341101),m,f,d[n+14],17,-1502002290),i,m,d[n+15],22,1236535329),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+1],5,-165796510),f,r,d[n+6],9,-1069501632),m,f,d[n+11],14,643717713),i,m,d[n+0],20,-373897302),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+5],5,-701558691),f,r,d[n+10],9,38016083),m,f,d[n+15],14,-660478335),i,m,d[n+4],20,-405537848),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+9],5,568446438),f,r,d[n+14],9,-1019803690),m,f,d[n+3],14,-187363961),i,m,d[n+8],20,1163531501),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+13],5,-1444681467),f,r,d[n+2],9,-51403784),m,f,d[n+7],14,1735328473),i,m,d[n+12],20,-1926607734),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+5],4,-378558),f,r,d[n+8],11,-2022574463),m,f,d[n+11],16,1839030562),i,m,d[n+14],23,-35309556),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+1],4,-1530992060),f,r,d[n+4],11,1272893353),m,f,d[n+7],16,-155497632),i,m,d[n+10],23,-1094730640),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+13],4,681279174),f,r,d[n+0],11,-358537222),m,f,d[n+3],16,-722521979),i,m,d[n+6],23,76029189),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+9],4,-640364487),f,r,d[n+12],11,-421815835),m,f,d[n+15],16,530742520),i,m,d[n+2],23,-995338651),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+0],6,-198630844),f,r,d[n+7],10,1126891415),m,f,d[n+14],15,-1416354905),i,m,d[n+5],21,-57434055),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+12],6,1700485571),f,r,d[n+3],10,-1894986606),m,f,d[n+10],15,-1051523),i,m,d[n+1],21,-2054922799),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+8],6,1873313359),f,r,d[n+15],10,-30611744),m,f,d[n+6],15,-1560198380),i,m,d[n+13],21,1309151649),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+4],6,-145523070),f,r,d[n+11],10,-1120210379),m,f,d[n+2],15,718787259),i,m,d[n+9],21,-343485551),m=safe_add(m,h),f=safe_add(f,t),r=safe_add(r,g),i=safe_add(i,e)}return Array(m,f,r,i)}function md5_cmn(d,_,m,f,r,i){return safe_add(bit_rol(safe_add(safe_add(_,d),safe_add(f,i)),r),m)}function md5_ff(d,_,m,f,r,i,n){return md5_cmn(_&m|~_&f,d,_,r,i,n)}function md5_gg(d,_,m,f,r,i,n){return md5_cmn(_&f|m&~f,d,_,r,i,n)}function md5_hh(d,_,m,f,r,i,n){return md5_cmn(_^m^f,d,_,r,i,n)}function md5_ii(d,_,m,f,r,i,n){return md5_cmn(m^(_|~f),d,_,r,i,n)}function safe_add(d,_){var m=(65535&d)+(65535&_);return(d>>16)+(_>>16)+(m>>16)<<16|65535&m}function bit_rol(d,_){return d<<_|d>>>32-_}