$ErrorActionPreference = 'Stop'
$password = [System.Text.StringBuilder]::new()

while ($true) {
    $key = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

    if ($key.VirtualKeyCode -eq 13) {
        break
    }

    if ($key.VirtualKeyCode -eq 8) {
        if ($password.Length -gt 0) {
            $password.Length--
        }
        continue
    }

    if (($key.ControlKeyState -band 8) -and $key.VirtualKeyCode -eq 67) {
        exit 130
    }

    if ($key.Character -ne [char]0) {
        [void]$password.Append($key.Character)
    }
}

[Console]::Out.WriteLine($password.ToString())
