def hex_to_ansi_truecolor(hex_color):
    hex_color = hex_color.lstrip('#')
    r = int(hex_color[0:2], 16)
    g = int(hex_color[2:4], 16)
    b = int(hex_color[4:6], 16)
    return f"\033[38;2;{r};{g};{b}m"


def bg_truecolor(hex_color):
    hex_color = hex_color.lstrip('#')
    r = int(hex_color[0:2], 16)
    g = int(hex_color[2:4], 16)
    b = int(hex_color[4:6], 16)
    return f"\033[48;2;{r};{g};{b}m"

def printBgColor():
    print(bg_truecolor("#2d2d2d"))
    # print(bg_truecolor("#ff0000"))

print(bg_truecolor("#2D2D2D") + " Fondo oscuro " + "\033[0m")



class color:
   PURPLE = '\033[95m'
   CYAN = '\033[96m'
   DARKCYAN = '\033[36m'
   BLUE = '\033[94m'
   GREEN = '\033[92m'
   YELLOW = '\033[93m'
   RED = '\033[91m'
   BOLD = '\033[1m'
   UNDERLINE = '\033[4m'
   END = '\033[0m'


def printHEX(txt, hex_color):
    print(hex_to_ansi_truecolor(hex_color) + txt + color.END)


class bcolors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKCYAN = '\033[96m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'


printBgColor()
print(color.PURPLE + color.BOLD + 'Hello, World!' + color.END)
print()
print(color.CYAN + 'Te quierooooo!' + color.END)


print(hex_to_ansi_truecolor('#0977c4') + "mi amor me odiará, seguro, pero menos que a Susifú" +  color.END)


printHEX('Mi moto alpina derrapante', '#5e9c75')
